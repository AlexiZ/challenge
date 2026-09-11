<?php

namespace App\Service;

use App\Entity\CityEdition;
use App\Entity\Trip;
use App\Entity\User;
use App\Enum\TripModeEnum;
use App\Repository\BonusPhotoRepository;
use App\Repository\TripRepository;

class StatsCalculator
{
    public function __construct(
        private readonly TripRepository $tripRepository,
        private readonly BonusPhotoRepository $bonusPhotoRepository,
        private readonly Co2Calculator $co2Calculator,
    ) {}

    /** @return array<string, mixed> */
    public function getUserStats(User $user, CityEdition $cityEdition): array
    {
        $kmBike = $this->tripRepository->getTotalDistanceByUserAndCityEdition($user, $cityEdition, TripModeEnum::Bike);
        $kmWalk = $this->tripRepository->getTotalDistanceByUserAndCityEdition($user, $cityEdition, TripModeEnum::Walk);
        $activeDays = $this->tripRepository->getActiveDaysByUserAndCityEdition($user, $cityEdition);
        $bonusPoints = $this->bonusPhotoRepository->getTotalApprovedPointsByUserAndCityEdition($user, $cityEdition);
        $co2 = $this->co2Calculator->calculateForUser($user, $cityEdition);

        $dayPoints = $activeDays * $cityEdition->getPointsPerDay();
        $bikePoints = $kmBike * $cityEdition->getPointsPerKmBike();
        $walkPoints = $kmWalk * $cityEdition->getPointsPerKmWalk();
        $totalPoints = round($dayPoints + $bikePoints + $walkPoints + $bonusPoints, 2);

        return [
            'km_bike' => round($kmBike, 2),
            'km_walk' => round($kmWalk, 2),
            'km_total' => round($kmBike + $kmWalk, 2),
            'active_days' => $activeDays,
            'bonus_points' => $bonusPoints,
            'total_points' => $totalPoints,
            'co2_kg' => $co2,
        ];
    }

    /** @return array<string, mixed> */
    public function getCityEditionStats(CityEdition $cityEdition): array
    {
        $totalKm = $this->tripRepository->getTotalCityDistanceByEdition($cityEdition);
        $participantCount = $cityEdition->getParticipants()->count();
        $targetProgress = $cityEdition->getTargetDistanceKm() > 0
            ? min(100, round($totalKm / $cityEdition->getTargetDistanceKm() * 100, 1))
            : 0;

        return [
            'total_km' => round($totalKm, 2),
            'participant_count' => $participantCount,
            'target_km' => $cityEdition->getTargetDistanceKm(),
            'target_progress_pct' => $targetProgress,
            'co2_kg' => $this->co2Calculator->calculateForCityEdition($cityEdition),
        ];
    }

    /**
     * Aggregated ranking stats for a city edition, used to rank cities against each other.
     * Mirrors the points formula used for the individual/team rankings (trip points + approved bonus photos),
     * excluding admins who don't count towards points.
     *
     * @return array<string, mixed>
     */
    public function getCityEditionRankingStats(CityEdition $cityEdition): array
    {
        $trips = array_filter(
            $this->tripRepository->findByCityEditionWithUsers($cityEdition),
            fn (Trip $trip) => !$trip->getUser()->isSuperAdmin() && !$trip->getUser()->isAdminCity(),
        );
        $bonusPoints = $this->bonusPhotoRepository->getBonusPointsPerUserByCityEdition($cityEdition);

        $perUser = [];
        foreach ($trips as $trip) {
            $uid = $trip->getUser()->getId();
            if (!isset($perUser[$uid])) {
                $perUser[$uid] = ['km' => 0.0, 'tripPoints' => 0.0, 'dates' => []];
            }
            $perUser[$uid]['km'] += $trip->getDistanceKm();
            $perUser[$uid]['tripPoints'] += $trip->getPointsGenerated();
            $perUser[$uid]['dates'][$trip->getTripDate()->format('Y-m-d')] = true;
        }

        $participantCount = count($perUser);
        $totalPoints = 0.0;
        $totalKm = 0.0;
        $totalActiveDays = 0;
        foreach ($perUser as $uid => $data) {
            $dayPoints = count($data['dates']) * $cityEdition->getPointsPerDay();
            $totalPoints += $data['tripPoints'] + $dayPoints + (float) ($bonusPoints[$uid] ?? 0.0);
            $totalKm += $data['km'];
            $totalActiveDays += count($data['dates']);
        }

        return [
            'participant_count' => $participantCount,
            'total_points' => round($totalPoints, 1),
            'avg_score' => $participantCount > 0 ? round($totalPoints / $participantCount, 1) : 0.0,
            'avg_distance_km' => $participantCount > 0 ? round($totalKm / $participantCount, 2) : 0.0,
            'avg_active_days' => $participantCount > 0 ? round($totalActiveDays / $participantCount, 1) : 0.0,
        ];
    }

    /**
     * Ranks city editions against each other by total points, descending.
     * Shared by every inter-city ranking display (city home, my challenge, global home).
     *
     * @param CityEdition[] $cityEditions
     * @return array<int, array{cityEdition: CityEdition, stats: array<string, mixed>}>
     */
    public function rankCityEditions(array $cityEditions): array
    {
        $ranking = [];
        foreach ($cityEditions as $ce) {
            $ranking[] = [
                'cityEdition' => $ce,
                'stats' => $this->getCityEditionRankingStats($ce),
            ];
        }

        usort($ranking, fn ($a, $b) => $b['stats']['total_points'] <=> $a['stats']['total_points']);

        return $ranking;
    }
}
