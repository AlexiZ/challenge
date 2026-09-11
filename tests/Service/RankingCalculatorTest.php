<?php

namespace App\Tests\Service;

use App\Entity\CityEdition;
use App\Entity\Team;
use App\Entity\Trip;
use App\Entity\User;
use App\Enum\TripModeEnum;
use App\Repository\BonusPhotoRepository;
use App\Repository\TeamRepository;
use App\Repository\TripRepository;
use App\Service\RankingCalculator;
use PHPUnit\Framework\TestCase;

class RankingCalculatorTest extends TestCase
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

    public function testIndividualRankingExcludesAdminsAndSortsByPoints(): void
    {
        $cityEdition = (new CityEdition())
            ->setPointsPerDay(2.0)
            ->setPointsPerKmBike(1.0)
            ->setPointsPerKmWalk(1.0);

        $participant = $this->makeUser(1);
        $admin = $this->makeUser(2);
        $admin->setRoles(['ROLE_ADMIN_CITY']);
        $inactiveParticipant = $this->makeUser(3);

        $cityEdition->addParticipant($participant);
        $cityEdition->addParticipant($admin);
        $cityEdition->addParticipant($inactiveParticipant);

        $trips = [
            $this->makeTrip($participant, $cityEdition, TripModeEnum::Bike, 10.0, '2026-01-01', 10.0),
            $this->makeTrip($admin, $cityEdition, TripModeEnum::Bike, 100.0, '2026-01-01', 100.0),
        ];

        $tripRepository = $this->createMock(TripRepository::class);
        $tripRepository->method('findByCityEditionWithUsers')->willReturn($trips);

        $bonusPhotoRepository = $this->createMock(BonusPhotoRepository::class);
        $bonusPhotoRepository->method('getBonusPointsPerUserByCityEdition')->willReturn([]);

        $calculator = new RankingCalculator($tripRepository, $bonusPhotoRepository, $this->createMock(TeamRepository::class));

        $userTripData = $calculator->buildUserTripData($cityEdition);
        $ranking = $calculator->buildIndividualRanking($cityEdition, $userTripData);

        // Admin's 100km trip must not appear at all — excluded from both trip data and ranking.
        self::assertCount(2, $ranking);
        self::assertSame($participant, $ranking[0]['user']);
        self::assertSame(12.0, $ranking[0]['points']); // 10 trip points + 1 active day * 2.0
        self::assertSame($inactiveParticipant, $ranking[1]['user']);
        self::assertSame(0.0, $ranking[1]['points']);
    }

    public function testTeamMemberRankingUsesSharedFormula(): void
    {
        $cityEdition = (new CityEdition())
            ->setPointsPerDay(2.0)
            ->setPointsPerKmBike(1.0)
            ->setPointsPerKmWalk(1.0);

        $member = $this->makeUser(1);
        $team = new Team();
        $team->addMember($member);

        $trips = [
            $this->makeTrip($member, $cityEdition, TripModeEnum::Bike, 10.0, '2026-01-01', 10.0),
        ];

        $tripRepository = $this->createMock(TripRepository::class);
        $tripRepository->method('findByCityEditionWithUsers')->willReturn($trips);

        $bonusPhotoRepository = $this->createMock(BonusPhotoRepository::class);
        $bonusPhotoRepository->method('getBonusPointsPerUserByCityEdition')->willReturn([1 => 5.0]);

        $calculator = new RankingCalculator($tripRepository, $bonusPhotoRepository, $this->createMock(TeamRepository::class));

        $userTripData = $calculator->buildUserTripData($cityEdition);
        $ranking = $calculator->buildTeamMemberRanking($cityEdition, $team, $userTripData);

        // 10 trip points + 1 active day * 2.0 + 5.0 bonus = 17.0
        self::assertSame(17.0, $ranking[0]['points']);
        self::assertNotNull($ranking[0]['lastDate']);
    }
}
