<?php

namespace App\Controller;

use App\Entity\Trip;
use App\Entity\User;
use App\Enum\BonusChallengeEnum;
use App\Enum\TripModeEnum;
use App\Form\TripType;
use App\Repository\BonusPhotoRepository;
use App\Repository\CityEditionRepository;
use App\Repository\TeamRepository;
use App\Repository\TripRepository;
use App\Service\ActiveCityEditionResolver;
use App\Service\Co2Calculator;
use App\Service\StatsCalculator;
use App\Service\TripPointsCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{citySlug}', requirements: ['citySlug' => '(?!(admin|connexion|inscription|deconnexion)(/|$))[a-z0-9][a-z0-9-]*'])]
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
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);
        $cityStats = $statsCalculator->getCityEditionStats($cityEdition);
        $allCityEditions = $cityEditionRepository->findLatestPerCity();
        $publicPhotos = $bonusPhotoRepository->findApprovedPublicByCityEdition($cityEdition);

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
            'allCityEditions' => $allCityEditions,
            'publicPhotos' => $publicPhotos,
            'challengePointsMap' => $challengePointsMap,
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
        $allCityEditions = $cityEditionRepository->findLatestPerCity();
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
            'allCityEditions' => $allCityEditions,
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

        $trip = new Trip();
        $trip->setUser($user);
        $trip->setCityEdition($cityEdition);

        $form = $this->createForm(TripType::class, $trip);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($trip);
            $em->flush();

            $this->addFlash('success', 'Trajet enregistré !');

            return $this->redirectToRoute('app_trip_entry', ['citySlug' => $citySlug]);
        }

        $recentTrips = $tripRepository->findByUserAndCityEdition($user, $cityEdition);
        $stats = $statsCalculator->getUserStats($user, $cityEdition);

        return $this->render('challenge/trip_entry.html.twig', [
            'cityEdition' => $cityEdition,
            'city' => $cityEdition->getCity(),
            'form' => $form,
            'recentTrips' => array_slice($recentTrips, 0, 10),
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
        TeamRepository $teamRepository,
        BonusPhotoRepository $bonusPhotoRepository,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        if (!$cityEdition->isRankingsPublic() && !$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $modeParam = $request->query->get('mode');
        $mode = $modeParam ? TripModeEnum::tryFrom($modeParam) : null;
        $view = $request->query->get('view', 'team');

        // All trips with users (2 queries: trips + users join)
        // Admins don't count towards points/rankings — they only administer the challenge.
        $allTrips = array_filter(
            $tripRepository->findByCityEditionWithUsers($cityEdition),
            fn (Trip $trip) => !$trip->getUser()->isSuperAdmin() && !$trip->getUser()->isAdminCity(),
        );

        // Bonus photo points per user (1 query)
        $bonusPoints = $bonusPhotoRepository->getBonusPointsPerUserByCityEdition($cityEdition);

        // All-modes data for edition stats
        $allUserData = [];
        $bikeUsers = [];
        $walkUsers = [];
        foreach ($allTrips as $trip) {
            $uid = $trip->getUser()->getId();
            if (!isset($allUserData[$uid])) {
                $allUserData[$uid] = ['tripPoints' => 0.0, 'dates' => []];
            }
            $allUserData[$uid]['tripPoints'] += $trip->getPointsGenerated();
            $allUserData[$uid]['dates'][$trip->getTripDate()->format('Y-m-d')] = true;
            if ($trip->getMode() === TripModeEnum::Bike) {
                $bikeUsers[$uid] = true;
            } else {
                $walkUsers[$uid] = true;
            }
        }
        $totalActive = count($allUserData);
        $bestAllModesPoints = 0.0;
        foreach ($allUserData as $uid => $data) {
            $total = $data['tripPoints'] + (float) ($bonusPoints[$uid] ?? 0.0);
            if ($total > $bestAllModesPoints) $bestAllModesPoints = $total;
        }

        // Mode-filtered per-user data for ranking
        $userTripData = [];
        foreach ($allTrips as $trip) {
            if ($mode !== null && $trip->getMode() !== $mode) continue;
            $uid = $trip->getUser()->getId();
            if (!isset($userTripData[$uid])) {
                $userTripData[$uid] = [
                    'user'       => $trip->getUser(),
                    'km'         => 0.0,
                    'tripPoints' => 0.0,
                    'dates'      => [],
                ];
            }
            $userTripData[$uid]['km'] += $trip->getDistanceKm();
            $userTripData[$uid]['tripPoints'] += $trip->getPointsGenerated();
            $userTripData[$uid]['dates'][$trip->getTripDate()->format('Y-m-d')] = true;
        }

        // Individual ranking (bonus always included, admins already excluded from $allTrips)
        $individualRanking = [];
        foreach ($userTripData as $uid => $data) {
            $bonus = (float) ($bonusPoints[$uid] ?? 0.0);
            $individualRanking[] = [
                'user'       => $data['user'],
                'km'         => round($data['km'], 1),
                'points'     => round($data['tripPoints'] + $bonus, 1),
                'activeDays' => count($data['dates']),
            ];
        }
        usort($individualRanking, fn ($a, $b) => $b['points'] <=> $a['points']);

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

        // Team ranking (1 query for teams + members)
        $teams = $teamRepository->findWithMembersByCity($cityEdition->getCity());
        $teamRanking = [];
        foreach ($teams as $team) {
            $teamKm = 0.0;
            $teamPoints = 0.0;
            $teamDays = 0;
            $active = 0;
            foreach ($team->getMembers() as $member) {
                $uid = $member->getId();
                if (!isset($userTripData[$uid])) continue;
                $bonus = (float) ($bonusPoints[$uid] ?? 0.0);
                $teamKm += $userTripData[$uid]['km'];
                $teamPoints += $userTripData[$uid]['tripPoints'] + $bonus;
                $teamDays += count($userTripData[$uid]['dates']);
                $active++;
            }
            if ($active < 2) continue;
            $teamRanking[] = [
                'team'              => $team,
                'km'                => round($teamKm, 1),
                'points'            => round($teamPoints, 1),
                'activeDays'        => $teamDays,
                'participants'      => $active,
                'ptsPerParticipant' => round($teamPoints / $active, 1),
            ];
        }
        usort($teamRanking, fn ($a, $b) => $b['ptsPerParticipant'] <=> $a['ptsPerParticipant']);

        // Current user team position
        $myTeamPosition = null;
        if ($this->getUser()) {
            $myUserId = $this->getUser()->getId();
            foreach ($teamRanking as $rank => $entry) {
                foreach ($entry['team']->getMembers() as $member) {
                    if ($member->getId() === $myUserId) {
                        $myTeamPosition = [
                            'rank'  => $rank + 1,
                            'total' => count($teamRanking),
                            'team'  => $entry['team'],
                        ];
                        break 2;
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
            'myTeamPosition'   => $myTeamPosition,
            'editionStats'     => $editionStats,
        ]);
    }
}
