<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileFormType;
use App\Repository\TripRepository;
use App\Repository\UserRepository;
use App\Service\ActiveCityEditionResolver;
use App\Service\StatsCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

class ProfileController extends AbstractController
{
    private const CITY_SLUG_REQUIREMENT = ['citySlug' => '(?!(admin|connexion|inscription|deconnexion|mot-de-passe-oublie)(/|$))[a-z0-9][a-z0-9-]*'];

    public function __construct(
        private readonly ActiveCityEditionResolver $cityEditionResolver,
    ) {}

    #[Route('/{citySlug}/profil/', name: 'app_profile', requirements: self::CITY_SLUG_REQUIREMENT)]
    public function index(
        string $citySlug,
        Request $request,
        EntityManagerInterface $em,
        StatsCalculator $statsCalculator,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $avatarFile = $form->get('avatarFile')->getData();
            if ($avatarFile) {
                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
                $oldAvatar = $user->getAvatar();
                if ($oldAvatar) {
                    $oldPath = $uploadsDir . '/' . $oldAvatar;
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $newFilename = bin2hex(random_bytes(8)) . '.' . $avatarFile->guessExtension();
                $avatarFile->move($uploadsDir, $newFilename);
                $user->setAvatar($newFilename);
            }

            $newPassword = $form->get('newPassword')->getData();
            if ($newPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            }

            $em->flush();
            $this->addFlash('success', 'Profil mis à jour.');
            return $this->redirectToRoute('app_profile', ['citySlug' => $citySlug]);
        }

        $stats = $statsCalculator->getUserStats($user, $cityEdition);

        return $this->render('profile/index.html.twig', [
            'cityEdition' => $cityEdition,
            'city' => $cityEdition->getCity(),
            'form' => $form,
            'stats' => $stats,
        ]);
    }

    #[Route('/{citySlug}/profil/regenerer-lien', name: 'app_profile_regenerate_token', methods: ['POST'], requirements: self::CITY_SLUG_REQUIREMENT)]
    public function regenerateToken(string $citySlug, Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('regenerate_token', $request->request->get('_csrf_token')))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->regeneratePersonalToken();
        $em->flush();

        $this->addFlash('success', 'Nouveau lien de connexion généré.');

        return $this->redirectToRoute('app_profile', ['citySlug' => $citySlug]);
    }

    #[Route('/{citySlug}/participants/{username}', name: 'app_profile_public', requirements: self::CITY_SLUG_REQUIREMENT)]
    public function publicProfile(
        string $citySlug,
        string $username,
        UserRepository $userRepository,
        StatsCalculator $statsCalculator,
        TripRepository $tripRepository,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        $profileUser = $userRepository->findOneBy(['username' => $username]);

        if ($profileUser === null || !$profileUser->isPublicProfile()) {
            throw $this->createNotFoundException();
        }

        $stats = $statsCalculator->getUserStats($profileUser, $cityEdition);
        $trips = $tripRepository->findByUserAndCityEdition($profileUser, $cityEdition);
        $heatmapData = $tripRepository->getDailyDistanceForHeatmap($profileUser, $cityEdition);

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        return $this->render('profile/public.html.twig', [
            'cityEdition' => $cityEdition,
            'city' => $cityEdition->getCity(),
            'profileUser' => $profileUser,
            'stats' => $stats,
            'trips' => $trips,
            'heatmapData' => $heatmapData,
            'isSelf' => $currentUser !== null && $currentUser->getId() === $profileUser->getId(),
        ]);
    }
}
