<?php

namespace App\Service;

use App\Entity\CityEdition;
use App\Entity\User;
use App\Enum\TripModeEnum;
use App\Repository\TripRepository;

class Co2Calculator
{
    private const CO2_GRAMS_PER_KM = 193.0;

    public function __construct(
        private readonly TripRepository $tripRepository,
    ) {}

    public function calculateForUser(User $user, CityEdition $cityEdition): float
    {
        $kmBike = $this->tripRepository->getTotalDistanceByUserAndCityEdition($user, $cityEdition, TripModeEnum::Bike);
        $kmWalk = $this->tripRepository->getTotalDistanceByUserAndCityEdition($user, $cityEdition, TripModeEnum::Walk);

        return round(($kmBike + $kmWalk) * self::CO2_GRAMS_PER_KM / 1000, 2);
    }

    public function calculateForCityEdition(CityEdition $cityEdition): float
    {
        $totalKm = $this->tripRepository->getTotalCityDistanceByEdition($cityEdition);

        return round($totalKm * self::CO2_GRAMS_PER_KM / 1000, 2);
    }

    public static function fromKm(float $km): float
    {
        return round($km * self::CO2_GRAMS_PER_KM / 1000, 2);
    }
}
