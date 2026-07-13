<?php

namespace App\Controller;

use App\Entity\BonusPhoto;
use App\Entity\User;
use App\Enum\BonusPhotoStatusEnum;
use App\Form\AdminUserType;
use App\Form\CityType;
use App\Repository\BonusPhotoRepository;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/{citySlug}/admin', requirements: ['citySlug' => '(?!(admin|connexion|inscription|deconnexion)(/|$))[a-z0-9][a-z0-9-]*'])]
class CityAdminController extends AbstractController
{
    public function __construct(
        private readonly ActiveCityEditionResolver $cityEditionResolver,
    ) {}

    #[Route('/', name: 'app_city_admin_dashboard')]
    #[IsGranted('ROLE_ADMIN_CITY')]
    public function dashboard(
        string $citySlug,
        StatsCalculator $statsCalculator,
        UserRepository $userRepository,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        $this->denyAccessUnlessGranted('CITY_MANAGE', $cityEdition->getCity());

        $cityStats = $statsCalculator->getCityEditionStats($cityEdition);
        $participants = $cityEdition->getParticipants();

        return $this->render('city_admin/dashboard.html.twig', [
            'cityEdition' => $cityEdition,
            'city' => $cityEdition->getCity(),
            'cityStats' => $cityStats,
            'participants' => $participants,
        ]);
    }

    #[Route('/message', name: 'app_city_admin_update_message', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN_CITY')]
    public function updateOrganizerMessage(
        string $citySlug,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);
        $this->denyAccessUnlessGranted('CITY_MANAGE', $cityEdition->getCity());

        if ($this->isCsrfTokenValid('update_message_' . $cityEdition->getId(), $request->request->get('_token'))) {
            $message = trim($request->request->get('message', ''));
            $cityEdition->setOrganizerMessage($message ?: null);
            $em->flush();
            $this->addFlash('success', 'Message des organisateurs mis à jour.');
        }

        return $this->redirectToRoute('app_city_admin_dashboard', ['citySlug' => $citySlug]);
    }

    #[Route('/defis-photo', name: 'app_city_admin_bonus_photos')]
    #[IsGranted('ROLE_ADMIN_CITY')]
    public function bonusPhotos(
        string $citySlug,
        BonusPhotoRepository $bonusPhotoRepository,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        $this->denyAccessUnlessGranted('CITY_MANAGE', $cityEdition->getCity());

        $pending = $bonusPhotoRepository->findPendingByCityEdition($cityEdition);

        return $this->render('city_admin/bonus_photos.html.twig', [
            'cityEdition' => $cityEdition,
            'city' => $cityEdition->getCity(),
            'pending' => $pending,
        ]);
    }

    #[Route('/defis-photo/{id}/approuver', name: 'app_city_admin_bonus_photo_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN_CITY')]
    public function approveBonusPhoto(
        string $citySlug,
        BonusPhoto $bonusPhoto,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        $this->denyAccessUnlessGranted('CITY_MANAGE', $cityEdition->getCity());

        if ($this->isCsrfTokenValid('approve_bonus_photo_' . $bonusPhoto->getId(), $request->request->get('_token'))) {
            /** @var User $admin */
            $admin = $this->getUser();
            $bonusPhoto->setStatus(BonusPhotoStatusEnum::Approved);
            $bonusPhoto->setReviewedAt(new \DateTime());
            $bonusPhoto->setReviewedBy($admin);

            $config = null;
            foreach ($cityEdition->getBonusPhotoConfigs() as $cfg) {
                if ($cfg->getChallenge() === $bonusPhoto->getChallenge()) {
                    $config = $cfg;
                    break;
                }
            }
            $bonusPhoto->setPointsAwarded($config?->getPoints() ?? $bonusPhoto->getChallenge()->defaultPoints());

            $em->flush();
            $this->addFlash('success', 'Défi photo approuvé.');
        }

        return $this->redirectToRoute('app_city_admin_bonus_photos', ['citySlug' => $citySlug]);
    }

    #[Route('/defis-photo/{id}/rejeter', name: 'app_city_admin_bonus_photo_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN_CITY')]
    public function rejectBonusPhoto(
        string $citySlug,
        BonusPhoto $bonusPhoto,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        $this->denyAccessUnlessGranted('CITY_MANAGE', $cityEdition->getCity());

        if ($this->isCsrfTokenValid('reject_bonus_photo_' . $bonusPhoto->getId(), $request->request->get('_token'))) {
            /** @var User $admin */
            $admin = $this->getUser();
            $bonusPhoto->setStatus(BonusPhotoStatusEnum::Rejected);
            $bonusPhoto->setReviewedAt(new \DateTime());
            $bonusPhoto->setReviewedBy($admin);
            $bonusPhoto->setPointsAwarded(0.0);
            $em->flush();
            $this->addFlash('success', 'Défi photo rejeté.');
        }

        return $this->redirectToRoute('app_city_admin_bonus_photos', ['citySlug' => $citySlug]);
    }

    #[Route('/trajets-suspects', name: 'app_city_admin_suspicious_trips')]
    #[IsGranted('ROLE_ADMIN_CITY')]
    public function suspiciousTrips(
        string $citySlug,
        TripRepository $tripRepository,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        $this->denyAccessUnlessGranted('CITY_MANAGE', $cityEdition->getCity());

        $suspicious = $tripRepository->findSuspicious($cityEdition);

        return $this->render('city_admin/suspicious_trips.html.twig', [
            'cityEdition' => $cityEdition,
            'city' => $cityEdition->getCity(),
            'suspicious' => $suspicious,
        ]);
    }

    #[Route('/configuration', name: 'app_city_admin_config')]
    #[IsGranted('ROLE_ADMIN_CITY')]
    public function config(
        string $citySlug,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        $this->denyAccessUnlessGranted('CITY_MANAGE', $cityEdition->getCity());

        return $this->render('city_admin/config.html.twig', [
            'cityEdition' => $cityEdition,
            'city' => $cityEdition->getCity(),
        ]);
    }

    // ── City info ──

    #[Route('/ville', name: 'app_city_admin_city_edit')]
    #[IsGranted('ROLE_ADMIN_CITY')]
    public function editCity(
        string $citySlug,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);
        $city = $cityEdition->getCity();

        $this->denyAccessUnlessGranted('CITY_MANAGE', $city);

        $form = $this->createForm(CityType::class, $city, ['show_slug' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Informations de la ville mises à jour.');
            return $this->redirectToRoute('app_city_admin_city_edit', ['citySlug' => $citySlug]);
        }

        return $this->render('city_admin/city_form.html.twig', [
            'form' => $form,
            'city' => $city,
            'cityEdition' => $cityEdition,
        ]);
    }

    // ── Participants ──

    #[Route('/participants', name: 'app_city_admin_participants')]
    #[IsGranted('ROLE_ADMIN_CITY')]
    public function participants(string $citySlug): Response
    {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        $this->denyAccessUnlessGranted('CITY_MANAGE', $cityEdition->getCity());

        return $this->render('city_admin/participants.html.twig', [
            'cityEdition' => $cityEdition,
            'city' => $cityEdition->getCity(),
            'participants' => $cityEdition->getParticipants(),
        ]);
    }

    #[Route('/participants/{id}/modifier', name: 'app_city_admin_participant_edit')]
    #[IsGranted('ROLE_ADMIN_CITY')]
    public function editParticipant(
        string $citySlug,
        User $participant,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        $this->denyAccessUnlessGranted('CITY_MANAGE', $cityEdition->getCity());

        $form = $this->createForm(AdminUserType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            if ($plain) {
                $participant->setPassword($hasher->hashPassword($participant, $plain));
            }
            $em->flush();
            $this->addFlash('success', 'Participant mis à jour.');
            return $this->redirectToRoute('app_city_admin_participants', ['citySlug' => $citySlug]);
        }

        return $this->render('city_admin/participant_form.html.twig', [
            'form' => $form,
            'participant' => $participant,
            'cityEdition' => $cityEdition,
            'city' => $cityEdition->getCity(),
        ]);
    }

    #[Route('/participants/{id}/retirer', name: 'app_city_admin_participant_remove', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN_CITY')]
    public function removeParticipant(
        string $citySlug,
        User $participant,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        $this->denyAccessUnlessGranted('CITY_MANAGE', $cityEdition->getCity());

        if ($this->isCsrfTokenValid('remove_participant_' . $participant->getId(), $request->request->get('_token'))) {
            $cityEdition->removeParticipant($participant);
            $em->flush();
            $this->addFlash('success', $participant->getFullName() . ' retiré(e) de l\'édition.');
        }

        return $this->redirectToRoute('app_city_admin_participants', ['citySlug' => $citySlug]);
    }
}
