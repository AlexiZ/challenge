<?php

namespace App\Repository;

use App\Entity\City;
use App\Entity\CityEdition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CityEdition>
 */
class CityEditionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CityEdition::class);
    }

    public function findActiveByCitySlug(string $slug): ?CityEdition
    {
        $now = new \DateTime();
        return $this->createQueryBuilder('ce')
            ->join('ce.city', 'c')
            ->join('ce.edition', 'e')
            ->where('c.slug = :slug')
            ->andWhere('e.startDate <= :now')
            ->andWhere('e.endDate >= :now')
            ->setParameter('slug', $slug)
            ->setParameter('now', $now)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLatestByCitySlug(string $slug): ?CityEdition
    {
        return $this->createQueryBuilder('ce')
            ->join('ce.city', 'c')
            ->join('ce.edition', 'e')
            ->where('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->orderBy('e.year', 'DESC')
            ->addOrderBy('e.startDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByCity(City $city): ?CityEdition
    {
        $now = new \DateTime();
        return $this->createQueryBuilder('ce')
            ->join('ce.edition', 'e')
            ->where('ce.city = :city')
            ->andWhere('e.startDate <= :now')
            ->andWhere('e.endDate >= :now')
            ->setParameter('city', $city)
            ->setParameter('now', $now)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLatestByCity(City $city): ?CityEdition
    {
        return $this->createQueryBuilder('ce')
            ->join('ce.edition', 'e')
            ->where('ce.city = :city')
            ->setParameter('city', $city)
            ->orderBy('e.year', 'DESC')
            ->addOrderBy('e.startDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns the most recent CityEdition per city (one entry per city).
     * @return CityEdition[]
     */
    public function findLatestPerCity(): array
    {
        $all = $this->createQueryBuilder('ce')
            ->select('ce', 'c', 'e')
            ->join('ce.city', 'c')
            ->join('ce.edition', 'e')
            ->orderBy('e.year', 'DESC')
            ->addOrderBy('e.startDate', 'DESC')
            ->getQuery()
            ->getResult();

        $byCity = [];
        foreach ($all as $ce) {
            $id = $ce->getCity()->getId();
            if (!isset($byCity[$id])) {
                $byCity[$id] = $ce;
            }
        }

        usort($byCity, fn ($a, $b) => strcmp($a->getCity()->getName(), $b->getCity()->getName()));

        return array_values($byCity);
    }

    /** @return CityEdition[] */
    public function findAllActiveEditions(): array
    {
        $now = new \DateTime();
        return $this->createQueryBuilder('ce')
            ->join('ce.edition', 'e')
            ->where('e.startDate <= :now')
            ->andWhere('e.endDate >= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
}
