<?php

namespace App\Repository;

use App\Entity\CityEdition;
use App\Entity\Trip;
use App\Entity\User;
use App\Enum\TripModeEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Trip>
 */
class TripRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trip::class);
    }

    /** @return Trip[] */
    public function findByUserAndCityEdition(User $user, CityEdition $cityEdition): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.cityEdition = :ce')
            ->setParameter('user', $user)
            ->setParameter('ce', $cityEdition)
            ->orderBy('t.tripDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalDistanceByUserAndCityEdition(User $user, CityEdition $cityEdition, TripModeEnum $mode): float
    {
        $result = $this->createQueryBuilder('t')
            ->select('COALESCE(SUM(t.distanceKm), 0) AS total')
            ->where('t.user = :user')
            ->andWhere('t.cityEdition = :ce')
            ->andWhere('t.mode = :mode')
            ->setParameter('user', $user)
            ->setParameter('ce', $cityEdition)
            ->setParameter('mode', $mode->value)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    public function getActiveDaysByUserAndCityEdition(User $user, CityEdition $cityEdition): int
    {
        $result = $this->createQueryBuilder('t')
            ->select('COUNT(DISTINCT DATE(t.tripDate)) AS activeDays')
            ->where('t.user = :user')
            ->andWhere('t.cityEdition = :ce')
            ->setParameter('user', $user)
            ->setParameter('ce', $cityEdition)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    public function getTotalCityDistanceByEdition(CityEdition $cityEdition): float
    {
        $result = $this->createQueryBuilder('t')
            ->select('COALESCE(SUM(t.distanceKm), 0) AS total')
            ->where('t.cityEdition = :ce')
            ->setParameter('ce', $cityEdition)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    /** @return array<array{date: string, distance: float}> */
    public function getDailyDistanceForHeatmap(User $user, CityEdition $cityEdition): array
    {
        return $this->createQueryBuilder('t')
            ->select('DATE(t.tripDate) AS date, SUM(t.distanceKm) AS distance')
            ->where('t.user = :user')
            ->andWhere('t.cityEdition = :ce')
            ->setParameter('user', $user)
            ->setParameter('ce', $cityEdition)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Trip[] - all trips for an edition with user relation eager-loaded */
    public function findByCityEditionWithUsers(CityEdition $cityEdition): array
    {
        return $this->createQueryBuilder('t')
            ->select('t', 'u')
            ->join('t.user', 'u')
            ->where('t.cityEdition = :ce')
            ->setParameter('ce', $cityEdition)
            ->getQuery()
            ->getResult();
    }

    /** @return Trip[] */
    public function findSuspicious(CityEdition $cityEdition): array
    {
        $edition = $cityEdition->getEdition();

        return $this->createQueryBuilder('t')
            ->where('t.cityEdition = :ce')
            ->andWhere(
                '(t.mode = :bike AND t.distanceKm > :maxBike) OR (t.mode = :walk AND t.distanceKm > :maxWalk) OR t.tripDate < :start OR t.tripDate > :end'
            )
            ->setParameter('ce', $cityEdition)
            ->setParameter('bike', TripModeEnum::Bike->value)
            ->setParameter('walk', TripModeEnum::Walk->value)
            ->setParameter('maxBike', $cityEdition->getSuspiciousDistanceBike())
            ->setParameter('maxWalk', $cityEdition->getSuspiciousDistanceWalk())
            ->setParameter('start', $edition?->getStartDate())
            ->setParameter('end', $edition?->getEndDate())
            ->orderBy('t.distanceKm', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
