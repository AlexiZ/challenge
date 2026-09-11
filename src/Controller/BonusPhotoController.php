<?php

namespace App\Controller;

use App\Entity\BonusPhoto;
use App\Entity\User;
use App\Enum\BonusChallengeEnum;
use App\Form\BonusPhotoType;
use App\Repository\BonusPhotoRepository;
use App\Service\ActiveCityEditionResolver;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{citySlug}/defis-photo', requirements: ['citySlug' => '(?!(admin|connexion|inscription|deconnexion|mot-de-passe-oublie)(/|$))[a-z0-9][a-z0-9-]*'])]
class BonusPhotoController extends AbstractController
{
    public function __construct(
        private readonly ActiveCityEditionResolver $cityEditionResolver,
    ) {}

    #[Route('/', name: 'app_bonus_photo_list')]
    public function list(
        string $citySlug,
        Request $request,
        BonusPhotoRepository $bonusPhotoRepository,
        EntityManagerInterface $em,
        FileUploader $fileUploader,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        /** @var User|null $user */
        $user = $this->getUser();

        // Points and description maps (configured > enum default)
        $challengePointsMap = [];
        foreach ($cityEdition->getBonusPhotoConfigs() as $config) {
            $challengePointsMap[$config->getChallenge()->value] = (int) $config->getPoints();
        }
        $challengeDescriptionMap = [];
        foreach (BonusChallengeEnum::cases() as $case) {
            $challengePointsMap[$case->value] ??= (int) $case->defaultPoints();
            $challengeDescriptionMap[$case->value] = $case->description();
        }

        // Submission form (only when enabled and authenticated)
        $submitForm = null;
        if ($cityEdition->isPhotoChallengesEnabled() && $user !== null) {
            $bonusPhoto = new BonusPhoto();
            $bonusPhoto->setUser($user);
            $bonusPhoto->setCityEdition($cityEdition);

            $submitForm = $this->createForm(BonusPhotoType::class, $bonusPhoto);
            $submitForm->handleRequest($request);

            if ($submitForm->isSubmitted() && $submitForm->isValid()) {
                $photoFile = $submitForm->get('photoFile')->getData();
                if ($photoFile) {
                    // Photo path is stored relative to public/uploads/bonus_photos/.
                    $relativeDir = $cityEdition->getCity()->getSlug() . '/' . $cityEdition->getEdition()->getYear();
                    $filename = $fileUploader->upload($photoFile, 'bonus_photos/' . $relativeDir);
                    $bonusPhoto->setPhoto($relativeDir . '/' . $filename);
                }

                $em->persist($bonusPhoto);
                $em->flush();
                $this->addFlash('success', 'Votre défi photo a été soumis et sera examiné par l\'administrateur.');
                return $this->redirectToRoute('app_bonus_photo_list', ['citySlug' => $citySlug]);
            }
        }

        $myPhotos = $user !== null
            ? $bonusPhotoRepository->findBy(['user' => $user, 'cityEdition' => $cityEdition], ['submittedAt' => 'DESC'])
            : [];

        // Compute display total (approved awarded + pending estimated)
        $myTotalBonusPoints = 0;
        foreach ($myPhotos as $photo) {
            if ($photo->getStatus()->value === 'approved') {
                $myTotalBonusPoints += $photo->getPointsAwarded() ?? 0;
            } elseif ($photo->getStatus()->value === 'pending') {
                $myTotalBonusPoints += $challengePointsMap[$photo->getChallenge()->value] ?? 0;
            }
        }

        $publicPhotos = $bonusPhotoRepository->findApprovedPublicByCityEdition($cityEdition);

        return $this->render('bonus_photo/list.html.twig', [
            'cityEdition'             => $cityEdition,
            'city'                    => $cityEdition->getCity(),
            'myPhotos'                => $myPhotos,
            'myTotalBonusPoints'      => $myTotalBonusPoints,
            'publicPhotos'            => $publicPhotos,
            'submitForm'              => $submitForm,
            'challengePointsMap'      => $challengePointsMap,
            'challengeDescriptionMap' => $challengeDescriptionMap,
        ]);
    }

}
