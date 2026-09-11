<?php

namespace App\Tests\Service;

use App\Entity\CityEdition;
use App\Entity\Trip;
use App\Entity\User;
use App\Enum\TripModeEnum;
use App\Repository\BonusPhotoRepository;
use App\Repository\TripRepository;
use App\Service\Co2Calculator;
use App\Service\StatsCalculator;
use PHPUnit\Framework\TestCase;

class StatsCalculatorTest extends TestCase
{
    private function makeUser(int $id): User
    {
        $user = new User();
        (new \ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }

    private function makeTrip(User $user, CityEdition $ce, TripModeEnum $mode, float $km, string $date, float $points): Trip
    {
        return (new Trip())
            ->setUser($user)
            ->setCityEdition($ce)
            ->setMode($mode)
            ->setDistanceKm($km)
            ->setTripDate(new \DateTime($date))
            ->setPointsGenerated($points);
    }

    public function testActiveDayPointsAreCountedOncePerDayAndIncludedInTotal(): void
    {
        $cityEdition = (new CityEdition())
            ->setPointsPerDay(2.0)
            ->setPointsPerKmBike(1.0)
            ->setPointsPerKmWalk(1.0);

        $user = $this->makeUser(1);

        // Two trips on the same day (different modes) must yield only one active-day point.
        $trips = [
            $this->makeTrip($user, $cityEdition, TripModeEnum::Bike, 10.0, '2026-01-01', 10.0),
            $this->makeTrip($user, $cityEdition, TripModeEnum::Walk, 5.0, '2026-01-01', 5.0),
            $this->makeTrip($user, $cityEdition, TripModeEnum::Bike, 3.0, '2026-01-02', 3.0),
        ];

        $tripRepository = $this->createMock(TripRepository::class);
        $tripRepository->method('findByCityEditionWithUsers')->willReturn($trips);

        $bonusPhotoRepository = $this->createMock(BonusPhotoRepository::class);
        $bonusPhotoRepository->method('getBonusPointsPerUserByCityEdition')->willReturn([]);

        $calculator = new StatsCalculator($tripRepository, $bonusPhotoRepository, $this->createMock(Co2Calculator::class));

        $stats = $calculator->getCityEditionRankingStats($cityEdition);

        // tripPoints: 10 + 5 + 3 = 18, dayPoints: 2 active days * 2.0 = 4 -> total 22
        self::assertSame(22.0, $stats['total_points']);
        self::assertSame(22.0, $stats['avg_score']);
    }
}
