<?php

namespace App\Repository;

use App\Entity\BonusPhoto;
use App\Entity\CityEdition;
use App\Entity\User;
use App\Enum\BonusPhotoStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BonusPhoto>
 */
class BonusPhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonusPhoto::class);
    }

    /** @return BonusPhoto[] */
    public function findPendingByCityEdition(CityEdition $cityEdition): array
    {
        return $this->createQueryBuilder('bp')
            ->where('bp.cityEdition = :ce')
            ->andWhere('bp.status = :status')
            ->setParameter('ce', $cityEdition)
            ->setParameter('status', BonusPhotoStatusEnum::Pending)
            ->orderBy('bp.submittedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return BonusPhoto[] */
    public function findApprovedPublicByCityEdition(CityEdition $cityEdition): array
    {
        return $this->createQueryBuilder('bp')
            ->where('bp.cityEdition = :ce')
            ->andWhere('bp.status = :status')
            ->andWhere('bp.consentToPublish = true')
            ->setParameter('ce', $cityEdition)
            ->setParameter('status', BonusPhotoStatusEnum::Approved)
            ->orderBy('bp.reviewedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return array<int, float> map of userId => total approved bonus points */
    public function getBonusPointsPerUserByCityEdition(CityEdition $cityEdition): array
    {
        $results = $this->createQueryBuilder('bp')
            ->select('IDENTITY(bp.user) AS userId, COALESCE(SUM(bp.pointsAwarded), 0) AS bonusPoints')
            ->where('bp.cityEdition = :ce')
            ->andWhere('bp.status = :status')
            ->setParameter('ce', $cityEdition)
            ->setParameter('status', BonusPhotoStatusEnum::Approved)
            ->groupBy('bp.user')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($results as $row) {
            $map[(int) $row['userId']] = (float) $row['bonusPoints'];
        }
        return $map;
    }

    public function getTotalApprovedPointsByUserAndCityEdition(User $user, CityEdition $cityEdition): float
    {
        $result = $this->createQueryBuilder('bp')
            ->select('COALESCE(SUM(bp.pointsAwarded), 0) AS total')
            ->where('bp.user = :user')
            ->andWhere('bp.cityEdition = :ce')
            ->andWhere('bp.status = :status')
            ->setParameter('user', $user)
            ->setParameter('ce', $cityEdition)
            ->setParameter('status', BonusPhotoStatusEnum::Approved)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }
}
