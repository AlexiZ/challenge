<?php

namespace App\Repository;

use App\Entity\City;
use App\Entity\CityEdition;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Team>
 */
class TeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    /** @return array<array{team: Team, totalPoints: float, totalKm: float, memberCount: int}> */
    public function getTeamRankingByCityEdition(CityEdition $cityEdition): array
    {
        return $this->createQueryBuilder('t')
            ->select(
                't',
                'COALESCE(SUM(tr.pointsGenerated), 0) AS totalPoints',
                'COALESCE(SUM(tr.distanceKm), 0) AS totalKm',
                'COUNT(DISTINCT m.id) AS memberCount'
            )
            ->join('t.members', 'm')
            ->leftJoin('m.trips', 'tr', 'WITH', 'tr.cityEdition = :ce')
            ->where('t.city = :city')
            ->setParameter('ce', $cityEdition)
            ->setParameter('city', $cityEdition->getCity())
            ->groupBy('t.id')
            ->orderBy('totalPoints', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Team[] - teams for a city with members eager-loaded */
    public function findWithMembersByCity(City $city): array
    {
        return $this->createQueryBuilder('t')
            ->select('t', 'm')
            ->join('t.members', 'm')
            ->where('t.city = :city')
            ->setParameter('city', $city)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findBySlugAndCity(string $slug, City $city): ?Team
    {
        return $this->createQueryBuilder('t')
            ->where('t.slug = :slug')
            ->andWhere('t.city = :city')
            ->setParameter('slug', $slug)
            ->setParameter('city', $city)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
