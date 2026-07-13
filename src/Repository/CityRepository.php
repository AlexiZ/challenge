<?php

namespace App\Repository;

use App\Entity\City;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<City>
 */
class CityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, City::class);
    }

    public function findBySlug(string $slug): ?City
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /** @return City[] */
    public function findAllWithActiveEdition(): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.cityEditions', 'ce')
            ->join('ce.edition', 'e')
            ->where('e.startDate <= :now')
            ->andWhere('e.endDate >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
