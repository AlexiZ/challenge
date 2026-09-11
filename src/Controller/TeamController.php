<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\User;
use App\Form\TeamType;
use App\Repository\BonusPhotoRepository;
use App\Repository\TeamRepository;
use App\Repository\TripRepository;
use App\Service\ActiveCityEditionResolver;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{citySlug}/equipes', requirements: ['citySlug' => '(?!(admin|connexion|inscription|deconnexion|mot-de-passe-oublie)(/|$))[a-z0-9][a-z0-9-]*'])]
class TeamController extends AbstractController
{
    public function __construct(
        private readonly ActiveCityEditionResolver $cityEditionResolver,
    ) {}

    #[Route('/creer', name: 'app_team_create')]
    public function create(
        string $citySlug,
        Request $request,
        EntityManagerInterface $em,
        SlugGenerator $slugGenerator,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);

        /** @var User $user */
        $user = $this->getUser();

        $team = new Team();
        $team->setCity($cityEdition->getCity());
        $team->setCreatedBy($user);

        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $team->setSlug($slugGenerator->generate($team->getName()));
            $team->addMember($user);
            $em->persist($team);
            $em->flush();

            $this->addFlash('success', 'Équipe créée avec succès !');
            return $this->redirectToRoute('app_team_show', [
                'citySlug' => $citySlug,
                'slug' => $team->getSlug(),
            ]);
        }

        return $this->render('team/create.html.twig', [
            'cityEdition' => $cityEdition,
            'city' => $cityEdition->getCity(),
            'form' => $form,
        ]);
    }

    #[Route('/{slug}/modifier', name: 'app_team_edit')]
    public function edit(
        string $citySlug,
        string $slug,
        Request $request,
        TeamRepository $teamRepository,
        EntityManagerInterface $em,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);
        $team = $teamRepository->findBySlugAndCity($slug, $cityEdition->getCity());

        if ($team === null) {
            throw $this->createNotFoundException('Équipe introuvable.');
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($team->getCreatedBy() !== $user && !$user->isSuperAdmin()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Équipe mise à jour avec succès !');
            return $this->redirectToRoute('app_team_show', [
                'citySlug' => $citySlug,
                'slug' => $team->getSlug(),
            ]);
        }

        return $this->render('team/edit.html.twig', [
            'cityEdition' => $cityEdition,
            'city' => $cityEdition->getCity(),
            'team' => $team,
            'form' => $form,
        ]);
    }

    #[Route('/{slug}', name: 'app_team_show')]
    public function show(
        string $citySlug,
        string $slug,
        TeamRepository $teamRepository,
        TripRepository $tripRepository,
        BonusPhotoRepository $bonusPhotoRepository,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);
        $team = $teamRepository->findBySlugAndCity($slug, $cityEdition->getCity());

        if ($team === null) {
            throw $this->createNotFoundException('Équipe introuvable.');
        }

        // Build per-user trip data for this edition
        $trips = $tripRepository->findByCityEditionWithUsers($cityEdition);
        $bonusPoints = $bonusPhotoRepository->getBonusPointsPerUserByCityEdition($cityEdition);

        $userTripData = [];
        foreach ($trips as $trip) {
            $uid = $trip->getUser()->getId();
            if (!isset($userTripData[$uid])) {
                $userTripData[$uid] = ['user' => $trip->getUser(), 'km' => 0.0, 'tripPoints' => 0.0, 'dates' => [], 'lastDate' => null];
            }
            $userTripData[$uid]['km'] += $trip->getDistanceKm();
            $userTripData[$uid]['tripPoints'] += $trip->getPointsGenerated();
            $userTripData[$uid]['dates'][$trip->getTripDate()->format('Y-m-d')] = true;
            $date = $trip->getTripDate();
            if ($userTripData[$uid]['lastDate'] === null || $date > $userTripData[$uid]['lastDate']) {
                $userTripData[$uid]['lastDate'] = $date;
            }
        }

        // Member ranking for this team
        $memberRanking = [];
        foreach ($team->getMembers() as $member) {
            $uid = $member->getId();
            $bonus = (float) ($bonusPoints[$uid] ?? 0.0);
            $data = $userTripData[$uid] ?? null;
            $dayPoints = $data ? count($data['dates']) * $cityEdition->getPointsPerDay() : 0.0;
            $memberRanking[] = [
                'user'       => $member,
                'km'         => $data ? round($data['km'], 2) : 0.0,
                'points'     => $data ? round($data['tripPoints'] + $dayPoints + $bonus, 1) : round($bonus, 1),
                'activeDays' => $data ? count($data['dates']) : 0,
                'lastDate'   => $data ? $data['lastDate'] : null,
            ];
        }
        usort($memberRanking, fn ($a, $b) => $b['points'] <=> $a['points']);

        // Team aggregate stats
        $teamKm = array_sum(array_column($memberRanking, 'km'));
        $teamTotalPoints = array_sum(array_column($memberRanking, 'points'));
        $teamDays = array_sum(array_column($memberRanking, 'activeDays'));
        $activeCount = count(array_filter($memberRanking, fn ($m) => $m['activeDays'] > 0));

        $teamStats = [
            'km'                => round($teamKm, 2),
            'points'            => round($teamTotalPoints, 1),
            'activeDays'        => $teamDays,
            'ptsPerParticipant' => $activeCount > 0 ? round($teamTotalPoints / $activeCount, 1) : 0.0,
        ];

        // Full team ranking (for general position)
        $teams = $teamRepository->findWithMembersByCity($cityEdition->getCity());
        $teamRanking = [];
        foreach ($teams as $t) {
            $tKm = 0.0; $tPoints = 0.0; $tDays = 0; $active = 0;
            foreach ($t->getMembers() as $member) {
                $uid = $member->getId();
                if (!isset($userTripData[$uid])) continue;
                $bonus = (float) ($bonusPoints[$uid] ?? 0.0);
                $tKm += $userTripData[$uid]['km'];
                $tPoints += $userTripData[$uid]['tripPoints'] + count($userTripData[$uid]['dates']) * $cityEdition->getPointsPerDay() + $bonus;
                $tDays += count($userTripData[$uid]['dates']);
                $active++;
            }
            if ($active < 2) continue;
            $teamRanking[] = [
                'team'              => $t,
                'km'                => round($tKm, 2),
                'points'            => round($tPoints, 1),
                'activeDays'        => $tDays,
                'participants'      => $active,
                'ptsPerParticipant' => round($tPoints / $active, 1),
            ];
        }
        usort($teamRanking, fn ($a, $b) => $b['ptsPerParticipant'] <=> $a['ptsPerParticipant']);

        // Find this team's general position
        $teamPosition = null;
        $teamRankingGap = null;
        foreach ($teamRanking as $rank => $entry) {
            if ($entry['team']->getId() === $team->getId()) {
                $teamPosition = ['rank' => $rank + 1, 'total' => count($teamRanking)];
                if ($rank > 0) {
                    $teamRankingGap = round($entry['ptsPerParticipant'] - $teamRanking[$rank - 1]['ptsPerParticipant'], 1);
                }
                break;
            }
        }

        return $this->render('team/show.html.twig', [
            'cityEdition'    => $cityEdition,
            'city'           => $cityEdition->getCity(),
            'team'           => $team,
            'teamStats'      => $teamStats,
            'memberRanking'  => $memberRanking,
            'teamRanking'    => $teamRanking,
            'teamPosition'   => $teamPosition,
            'teamRankingGap' => $teamRankingGap,
        ]);
    }

    #[Route('/{slug}/rejoindre', name: 'app_team_join', methods: ['POST'])]
    public function join(
        string $citySlug,
        string $slug,
        TeamRepository $teamRepository,
        EntityManagerInterface $em,
        Request $request,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);
        $team = $teamRepository->findBySlugAndCity($slug, $cityEdition->getCity());

        if ($team === null) {
            throw $this->createNotFoundException();
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('join_team_' . $team->getId(), $request->request->get('_token'))) {
            $team->addMember($user);
            $em->flush();
            $this->addFlash('success', 'Vous avez rejoint l\'équipe ' . $team->getName() . ' !');
        }

        return $this->redirectToRoute('app_team_show', ['citySlug' => $citySlug, 'slug' => $slug]);
    }

    #[Route('/{slug}/quitter', name: 'app_team_leave', methods: ['POST'])]
    public function leave(
        string $citySlug,
        string $slug,
        TeamRepository $teamRepository,
        EntityManagerInterface $em,
        Request $request,
    ): Response {
        $cityEdition = $this->cityEditionResolver->resolve($citySlug);
        $team = $teamRepository->findBySlugAndCity($slug, $cityEdition->getCity());

        if ($team === null) {
            throw $this->createNotFoundException();
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('leave_team_' . $team->getId(), $request->request->get('_token'))) {
            $team->removeMember($user);
            $em->flush();
            $this->addFlash('success', 'Vous avez quitté l\'équipe.');
        }

        return $this->redirectToRoute('app_team_show', ['citySlug' => $citySlug, 'slug' => $slug]);
    }
}
