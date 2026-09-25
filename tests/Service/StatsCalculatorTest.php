<?php

namespace App\Tests\Service;

use App\Entity\CityEdition;
use App\Entity\Edition;
use App\Entity\Trip;
use App\Entity\User;
use App\Enum\TripModeEnum;
use App\Repository\BonusPhotoRepository;
use App\Repository\TeamRepository;
use App\Repository\TripRepository;
use App\Service\Co2Calculator;
use App\Service\RankingCalculator;
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
        $cityEdition = (new CityEdition())->setEdition((new Edition())
            ->setPointsPerDay(2.0)
            ->setPointsPerKmBike(1.0)
            ->setPointsPerKmWalk(1.0));

        $user = $this->makeUser(1);
        $photoOnlyUser = $this->makeUser(2);
        $cityEdition->addParticipant($user)->addParticipant($photoOnlyUser);

        // Two trips on the same day (different modes) must yield only one active-day point.
        $trips = [
            $this->makeTrip($user, $cityEdition, TripModeEnum::Bike, 10.0, '2026-01-01', 10.0),
            $this->makeTrip($user, $cityEdition, TripModeEnum::Walk, 5.0, '2026-01-01', 5.0),
            $this->makeTrip($user, $cityEdition, TripModeEnum::Bike, 3.0, '2026-01-02', 3.0),
        ];

        $tripRepository = $this->createMock(TripRepository::class);
        $tripRepository->method('findByCityEditionWithUsers')->willReturn($trips);

        $bonusPhotoRepository = $this->createMock(BonusPhotoRepository::class);
        $bonusPhotoRepository->method('getBonusPointsPerUserByCityEdition')->willReturn([2 => 6.0]);

        $rankingCalculator = new RankingCalculator($tripRepository, $bonusPhotoRepository, $this->createMock(TeamRepository::class));

        $calculator = new StatsCalculator($tripRepository, $bonusPhotoRepository, $this->createMock(Co2Calculator::class), $rankingCalculator);

        $stats = $calculator->getCityEditionRankingStats($cityEdition);

        // user 1: tripPoints 10 + 5 + 3 = 18, dayPoints 2 active days * 2.0 = 4 -> 22
        // user 2: no trips, 6 approved bonus points -> still counted
        self::assertSame(2, $stats['participant_count']);
        self::assertSame(28.0, $stats['total_points']);
        self::assertSame(14.0, $stats['avg_score']);
    }
}
