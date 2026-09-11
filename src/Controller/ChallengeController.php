<?php

namespace App\Controller;

use App\Entity\Trip;
use App\Entity\User;
use App\Enum\BonusChallengeEnum;
use App\Enum\TripModeEnum;
use App\Form\TripType;
use App\Repository\BonusPhotoRepository;
use App\Repository\CityEditionRepository;
use App\Repository\TripRepository;
use App\Service\ActiveCityEditionResolver;
use App\Service\Co2Calculator;
use App\Service\RankingCalculator;
use App\Service\StatsCalculator;
use App\Service\TripPointsCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{citySlug}', requirements: ['citySlug' => '(?!(admin|connexion|inscription|deconnexion|mot-de-passe-oublie)(/|$))[a-z0-9][a-z0-9-]*'])]
class ChallengeController extends AbstractController
{
    public function __construct(
        private readonly ActiveCityEditionResolver $cityEditionResolver,
    ) {}

    #[Route('/', name: 'app_city_home')]
    public function cityHome(
        string $citySlug,
        StatsCalculator $statsCalculator,
        CityEditionRepository $cityEditionRepository,
        BonusPhotoRepository $bonusPhotoRepository,
        TripRepository $tripRepository,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);
        $cityStats = $statsCalculator->getCityEditionStats($cityEdition);
        $cityRanking = $statsCalculator->rankCityEditions($cityEditionRepository->findLatestPerCity());
        $publicPhotos = $bonusPhotoRepository->findApprovedPublicByCityEdition($cityEdition);
        $recentParticipants = $tripRepository->findRecentParticipants($cityEdition);

        // Points and description maps (configured > enum default)
        $challengePointsMap = [];
        foreach ($cityEdition->getBonusPhotoConfigs() as $config) {
            $challengePointsMap[$config->getChallenge()->value] = (int) $config->getPoints();
        }
        foreach (BonusChallengeEnum::cases() as $case) {
            $challengePointsMap[$case->value] ??= (int) $case->defaultPoints();
        }

        return $this->render('challenge/city_home.html.twig', [
            'cityEdition' => $cityEdition,
            'city' => $cityEdition->getCity(),
            'cityStats' => $cityStats,
            'cityRanking' => $cityRanking,
            'publicPhotos' => $publicPhotos,
            'challengePointsMap' => $challengePointsMap,
            'recentParticipants' => $recentParticipants,
        ]);
    }

    #[Route('/le-challenge', name: 'app_challenge_info')]
    public function challengeInfo(string $citySlug): Response
    {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        return $this->render('challenge/info.html.twig', [
            'cityEdition' => $cityEdition,
            'city' => $cityEdition->getCity(),
        ]);
    }

    #[Route('/mon-challenge', name: 'app_my_challenge')]
    public function myChallenge(
        string $citySlug,
        TripRepository $tripRepository,
        StatsCalculator $statsCalculator,
        CityEditionRepository $cityEditionRepository,
        BonusPhotoRepository $bonusPhotoRepository,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        /** @var User $user */
        $user = $this->getUser();

        $trips = $tripRepository->findByUserAndCityEdition($user, $cityEdition);
        $stats = $statsCalculator->getUserStats($user, $cityEdition);
        $heatmapData = $tripRepository->getDailyDistanceForHeatmap($user, $cityEdition);
        $cityStats = $statsCalculator->getCityEditionStats($cityEdition);
        $cityRanking = $statsCalculator->rankCityEditions($cityEditionRepository->findLatestPerCity());
        $myBonusPhotos = $bonusPhotoRepository->findBy(
            ['user' => $user, 'cityEdition' => $cityEdition],
            ['submittedAt' => 'DESC'],
        );

        return $this->render('challenge/my_challenge.html.twig', [
            'cityEdition' => $cityEdition,
            'city' => $cityEdition->getCity(),
            'trips' => $trips,
            'stats' => $stats,
            'heatmapData' => $heatmapData,
            'cityStats' => $cityStats,
            'cityRanking' => $cityRanking,
            'myBonusPhotos' => $myBonusPhotos,
        ]);
    }

    #[Route('/saisie-trajets', name: 'app_trip_entry')]
    public function tripEntry(
        string $citySlug,
        Request $request,
        EntityManagerInterface $em,
        TripRepository $tripRepository,
        StatsCalculator $statsCalculator,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        if (!$cityEdition->isTripsEntryOpen()) {
            $this->addFlash('warning', 'La saisie des trajets est actuellement fermée.');

            return $this->redirectToRoute('app_my_challenge', ['citySlug' => $citySlug]);
        }

        /** @var User $user */
        $user = $this->getUser();

        $edition = $cityEdition->getEdition();

        $trip = new Trip();
        $trip->setUser($user);
        $trip->setCityEdition($cityEdition);
        if (!$edition->isBikeModeEnabled()) {
            $trip->setMode(TripModeEnum::Walk);
        }

        $form = $this->createForm(TripType::class, $trip);
        if ($cityEdition->isActive()) {
            $form->handleRequest($request);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if (($trip->getMode() === TripModeEnum::Bike && !$edition->isBikeModeEnabled())
                || ($trip->getMode() === TripModeEnum::Walk && !$edition->isWalkModeEnabled())
            ) {
                $form->get('mode')->addError(new FormError('Ce mode de transport n\'est pas actif pour l\'édition en cours.'));
            } else {
                $em->persist($trip);
                $em->flush();

                $this->addFlash('success', 'Trajet enregistré !');

                return $this->redirectToRoute('app_trip_entry', ['citySlug' => $citySlug]);
            }
        }

        $allTrips = $tripRepository->findByUserAndCityEdition($user, $cityEdition);
        $stats = $statsCalculator->getUserStats($user, $cityEdition);

        // Only the first trip entered for a given day earns the active-day point,
        // regardless of transport mode — mirrors StatsCalculator's per-day dedup.
        $firstTripIdByDate = [];
        foreach ($allTrips as $trip) {
            $date = $trip->getTripDate()->format('Y-m-d');
            if (!isset($firstTripIdByDate[$date]) || $trip->getId() < $firstTripIdByDate[$date]) {
                $firstTripIdByDate[$date] = $trip->getId();
            }
        }
        $dayPointTripIds = array_values($firstTripIdByDate);

        return $this->render('challenge/trip_entry.html.twig', [
            'cityEdition' => $cityEdition,
            'city' => $cityEdition->getCity(),
            'form' => $form,
            'recentTrips' => array_slice($allTrips, 0, 10),
            'dayPointTripIds' => $dayPointTripIds,
            'stats' => $stats,
        ]);
    }

    #[Route('/saisie-trajets/{id}/supprimer', name: 'app_trip_delete', methods: ['POST'])]
    public function deleteTrip(
        string $citySlug,
        Trip $trip,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        /** @var User $user */
        $user = $this->getUser();

        if ($trip->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_trip_' . $trip->getId(), $request->request->get('_token'))) {
            $em->remove($trip);
            $em->flush();
            $this->addFlash('success', 'Trajet supprimé.');
        }

        return $this->redirectToRoute('app_trip_entry', ['citySlug' => $citySlug]);
    }

    #[Route('/participants', name: 'app_participants')]
    public function rankings(
        string $citySlug,
        Request $request,
        TripRepository $tripRepository,
        RankingCalculator $rankingCalculator,
        BonusPhotoRepository $bonusPhotoRepository,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        if (!$cityEdition->isRankingsPublic() && !$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $modeParam = $request->query->get('mode');
        $mode = $modeParam ? TripModeEnum::tryFrom($modeParam) : null;
        $view = $request->query->get('view', 'team');

        // All-modes data (edition stats always reflect every mode, regardless of the view filter)
        $allUserTripData = $rankingCalculator->buildUserTripData($cityEdition);
        $userTripData = null !== $mode ? $rankingCalculator->buildUserTripData($cityEdition, $mode) : $allUserTripData;

        // Bike/walk participation rates for edition stats
        $bikeUsers = [];
        $walkUsers = [];
        $allTrips = array_filter(
            $tripRepository->findByCityEditionWithUsers($cityEdition),
            fn (Trip $trip) => !$trip->getUser()->isSuperAdmin() && !$trip->getUser()->isAdminCity(),
        );
        foreach ($allTrips as $trip) {
            $uid = $trip->getUser()->getId();
            if ($trip->getMode() === TripModeEnum::Bike) {
                $bikeUsers[$uid] = true;
            } else {
                $walkUsers[$uid] = true;
            }
        }
        $totalActive = count($allUserTripData);

        $bonusPoints = $bonusPhotoRepository->getBonusPointsPerUserByCityEdition($cityEdition);
        $bestAllModesPoints = 0.0;
        foreach ($allUserTripData as $uid => $data) {
            $dayPoints = count($data['dates']) * $cityEdition->getPointsPerDay();
            $total = $data['tripPoints'] + $dayPoints + (float) ($bonusPoints[$uid] ?? 0.0);
            $bestAllModesPoints = max($bestAllModesPoints, $total);
        }

        $individualRanking = $rankingCalculator->buildIndividualRanking($cityEdition, $userTripData);

        // Current user individual position
        $myPosition = null;
        if ($this->getUser()) {
            $myUserId = $this->getUser()->getId();
            foreach ($individualRanking as $rank => $entry) {
                if ($entry['user']->getId() === $myUserId) {
                    $myPosition = [
                        'rank'   => $rank + 1,
                        'points' => $entry['points'],
                        'total'  => count($individualRanking),
                        'diff'   => $rank > 0
                            ? round($entry['points'] - $individualRanking[$rank - 1]['points'], 1)
                            : null,
                    ];
                    break;
                }
            }
        }

        $teamRanking = $rankingCalculator->buildTeamRanking($cityEdition, $userTripData);

        // Current user team position(s) — a user can belong to several teams
        $myTeamPositions = [];
        if ($this->getUser()) {
            $myUserId = $this->getUser()->getId();
            foreach ($teamRanking as $rank => $entry) {
                foreach ($entry['team']->getMembers() as $member) {
                    if ($member->getId() === $myUserId) {
                        $myTeamPositions[] = [
                            'rank'  => $rank + 1,
                            'total' => count($teamRanking),
                            'team'  => $entry['team'],
                        ];
                        break;
                    }
                }
            }
        }

        $editionStats = [
            'teamCount'        => count($teamRanking),
            'participantCount' => count($individualRanking),
            'bestIndividual'   => round($bestAllModesPoints, 1),
            'bestTeam'         => !empty($teamRanking) ? $teamRanking[0]['ptsPerParticipant'] : 0,
            'veloRate'         => $totalActive > 0 ? round(count($bikeUsers) / $totalActive * 100) : 0,
            'marcheRate'       => $totalActive > 0 ? round(count($walkUsers) / $totalActive * 100) : 0,
        ];

        return $this->render('challenge/rankings.html.twig', [
            'cityEdition'      => $cityEdition,
            'city'             => $cityEdition->getCity(),
            'view'             => $view,
            'mode'             => $mode,
            'teamRanking'      => $teamRanking,
            'individualRanking' => $individualRanking,
            'myPosition'       => $myPosition,
            'myTeamPositions'  => $myTeamPositions,
            'editionStats'     => $editionStats,
        ]);
    }
}
