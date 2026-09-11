<?php

namespace App\Service;

use App\Entity\CityEdition;
use App\Entity\Team;
use App\Entity\Trip;
use App\Entity\User;
use App\Enum\TripModeEnum;
use App\Repository\BonusPhotoRepository;
use App\Repository\TeamRepository;
use App\Repository\TripRepository;

/**
 * Single source of truth for the points formula (trip points + active-day points +
 * approved bonus photos, admins excluded) shared by the individual ranking, the
 * team ranking, and the inter-city ranking. Previously reimplemented independently
 * in ChallengeController, TeamController and StatsCalculator — any formula change
 * had to be kept in sync across all three by hand.
 */
class RankingCalculator
{
    public function __construct(
        private readonly TripRepository $tripRepository,
        private readonly BonusPhotoRepository $bonusPhotoRepository,
        private readonly TeamRepository $teamRepository,
    ) {}

    /**
     * Per-user aggregates (km, trip points, active dates, last trip date) for a city
     * edition, optionally filtered to a single transport mode. Admins are always excluded.
     *
     * @return array<int, array{user: User, km: float, tripPoints: float, dates: array<string, true>, lastDate: ?\DateTimeInterface}>
     */
    public function buildUserTripData(CityEdition $cityEdition, ?TripModeEnum $mode = null): array
    {
        $trips = array_filter(
            $this->tripRepository->findByCityEditionWithUsers($cityEdition),
            fn (Trip $trip) => !$trip->getUser()->isSuperAdmin() && !$trip->getUser()->isAdminCity()
                && (null === $mode || $trip->getMode() === $mode),
        );

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
            if (null === $userTripData[$uid]['lastDate'] || $date > $userTripData[$uid]['lastDate']) {
                $userTripData[$uid]['lastDate'] = $date;
            }
        }

        return $userTripData;
    }

    /**
     * Individual ranking — every registered participant appears, even with no trips yet.
     *
     * @param array<int, array{user: User, km: float, tripPoints: float, dates: array<string, true>}> $userTripData
     * @return array<int, array{user: User, km: float, points: float, activeDays: int}>
     */
    public function buildIndividualRanking(CityEdition $cityEdition, array $userTripData): array
    {
        $bonusPoints = $this->bonusPhotoRepository->getBonusPointsPerUserByCityEdition($cityEdition);

        $ranking = [];
        foreach ($cityEdition->getParticipants() as $user) {
            if ($user->isSuperAdmin() || $user->isAdminCity()) {
                continue;
            }
            $uid = $user->getId();
            $data = $userTripData[$uid] ?? null;
            $bonus = (float) ($bonusPoints[$uid] ?? 0.0);
            $dayPoints = $data ? count($data['dates']) * $cityEdition->getPointsPerDay() : 0.0;
            $ranking[] = [
                'user'       => $user,
                'km'         => $data ? round($data['km'], 2) : 0.0,
                'points'     => round(($data['tripPoints'] ?? 0.0) + $dayPoints + $bonus, 1),
                'activeDays' => $data ? count($data['dates']) : 0,
            ];
        }

        usort($ranking, fn ($a, $b) => $b['points'] <=> $a['points']);

        return $ranking;
    }

    /**
     * Ranking of one team's own members against each other.
     *
     * @param array<int, array{user: User, km: float, tripPoints: float, dates: array<string, true>, lastDate: ?\DateTimeInterface}> $userTripData
     * @return array<int, array{user: User, km: float, points: float, activeDays: int, lastDate: ?\DateTimeInterface}>
     */
    public function buildTeamMemberRanking(CityEdition $cityEdition, Team $team, array $userTripData): array
    {
        $bonusPoints = $this->bonusPhotoRepository->getBonusPointsPerUserByCityEdition($cityEdition);

        $ranking = [];
        foreach ($team->getMembers() as $member) {
            $uid = $member->getId();
            $bonus = (float) ($bonusPoints[$uid] ?? 0.0);
            $data = $userTripData[$uid] ?? null;
            $dayPoints = $data ? count($data['dates']) * $cityEdition->getPointsPerDay() : 0.0;
            $ranking[] = [
                'user'       => $member,
                'km'         => $data ? round($data['km'], 2) : 0.0,
                'points'     => $data ? round($data['tripPoints'] + $dayPoints + $bonus, 1) : round($bonus, 1),
                'activeDays' => $data ? count($data['dates']) : 0,
                'lastDate'   => $data ? $data['lastDate'] : null,
            ];
        }

        usort($ranking, fn ($a, $b) => $b['points'] <=> $a['points']);

        return $ranking;
    }

    /**
     * Team ranking for every team in the city, ranked by points per active participant.
     *
     * @param array<int, array{user: User, km: float, tripPoints: float, dates: array<string, true>}> $userTripData
     * @return array<int, array{team: Team, km: float, points: float, activeDays: int, participants: int, ptsPerParticipant: float}>
     */
    public function buildTeamRanking(CityEdition $cityEdition, array $userTripData, int $minActiveMembers = 0): array
    {
        $bonusPoints = $this->bonusPhotoRepository->getBonusPointsPerUserByCityEdition($cityEdition);
        $teams = $this->teamRepository->findWithMembersByCity($cityEdition->getCity());

        $ranking = [];
        foreach ($teams as $team) {
            $km = 0.0;
            $points = 0.0;
            $days = 0;
            $active = 0;
            foreach ($team->getMembers() as $member) {
                $uid = $member->getId();
                if (!isset($userTripData[$uid])) {
                    continue;
                }
                $bonus = (float) ($bonusPoints[$uid] ?? 0.0);
                $km += $userTripData[$uid]['km'];
                $points += $userTripData[$uid]['tripPoints'] + count($userTripData[$uid]['dates']) * $cityEdition->getPointsPerDay() + $bonus;
                $days += count($userTripData[$uid]['dates']);
                ++$active;
            }
            if ($active < $minActiveMembers) {
                continue;
            }
            $ranking[] = [
                'team'              => $team,
                'km'                => round($km, 2),
                'points'            => round($points, 1),
                'activeDays'        => $days,
                'participants'      => $active,
                'ptsPerParticipant' => $active > 0 ? round($points / $active, 1) : 0.0,
            ];
        }

        usort($ranking, fn ($a, $b) => $b['ptsPerParticipant'] <=> $a['ptsPerParticipant']);

        return $ranking;
    }
}
