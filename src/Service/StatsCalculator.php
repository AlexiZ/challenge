<?php

namespace App\Service;

use App\Entity\CityEdition;
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
            'km_bike' => round($kmBike, 1),
            'km_walk' => round($kmWalk, 1),
            'km_total' => round($kmBike + $kmWalk, 1),
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
            'total_km' => round($totalKm, 1),
            'participant_count' => $participantCount,
            'target_km' => $cityEdition->getTargetDistanceKm(),
            'target_progress_pct' => $targetProgress,
            'co2_kg' => $this->co2Calculator->calculateForCityEdition($cityEdition),
        ];
    }
}
