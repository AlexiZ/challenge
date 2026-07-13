<?php

namespace App\Repository;

use App\Entity\Edition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Edition>
 */
class EditionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Edition::class);
    }

    public function findActive(): ?Edition
    {
        $now = new \DateTime();
        return $this->createQueryBuilder('e')
            ->where('e.startDate <= :now')
            ->andWhere('e.endDate >= :now')
            ->setParameter('now', $now)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return Edition[] */
    public function findAllOrderedByYear(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.year', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
