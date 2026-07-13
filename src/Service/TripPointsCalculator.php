<?php

namespace App\Service;

use App\Entity\CityEdition;
use App\Entity\Trip;
use App\Entity\User;
use App\Enum\TripModeEnum;
use App\Repository\TripRepository;

class TripPointsCalculator
{
    public function __construct(
        private readonly TripRepository $tripRepository,
    ) {}

    public function calculateTripPoints(Trip $trip): float
    {
        $ce = $trip->getCityEdition();
        if ($ce === null) {
            return 0.0;
        }

        return match ($trip->getMode()) {
            TripModeEnum::Bike => round($trip->getDistanceKm() * $ce->getPointsPerKmBike(), 2),
            TripModeEnum::Walk => round($trip->getDistanceKm() * $ce->getPointsPerKmWalk(), 2),
        };
    }

    public function calculateUserTotalPoints(User $user, CityEdition $cityEdition): float
    {
        $activeDays = $this->tripRepository->getActiveDaysByUserAndCityEdition($user, $cityEdition);
        $kmBike = $this->tripRepository->getTotalDistanceByUserAndCityEdition($user, $cityEdition, TripModeEnum::Bike);
        $kmWalk = $this->tripRepository->getTotalDistanceByUserAndCityEdition($user, $cityEdition, TripModeEnum::Walk);

        $dayPoints = $activeDays * $cityEdition->getPointsPerDay();
        $bikePoints = $kmBike * $cityEdition->getPointsPerKmBike();
        $walkPoints = $kmWalk * $cityEdition->getPointsPerKmWalk();

        return round($dayPoints + $bikePoints + $walkPoints, 2);
    }

    public function recalculateForCityEdition(CityEdition $cityEdition): void
    {
        $trips = $cityEdition->getTrips();
        foreach ($trips as $trip) {
            $trip->setPointsGenerated($this->calculateTripPoints($trip));
        }
    }
}
