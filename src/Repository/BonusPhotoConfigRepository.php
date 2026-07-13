<?php

namespace App\Repository;

use App\Entity\BonusPhotoConfig;
use App\Entity\CityEdition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BonusPhotoConfig>
 */
class BonusPhotoConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonusPhotoConfig::class);
    }

    /** @return BonusPhotoConfig[] */
    public function findByCityEdition(CityEdition $cityEdition): array
    {
        return $this->findBy(['cityEdition' => $cityEdition]);
    }
}
