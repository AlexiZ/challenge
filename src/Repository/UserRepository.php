<?php

namespace App\Repository;

use App\Entity\City;
use App\Entity\CityEdition;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findOneByPersonalToken(string $token): ?User
    {
        return $this->findOneBy(['personalToken' => $token]);
    }

    /** @return User[] */
    public function findByCity(City $city): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.city = :city')
            ->setParameter('city', $city)
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return array<array{user: User, totalPoints: float, totalKm: float, activeDays: int}> */
    public function getRankingByCityEdition(CityEdition $cityEdition): array
    {
        return $this->createQueryBuilder('u')
            ->select(
                'u',
                'COALESCE(SUM(t.pointsGenerated), 0) + COALESCE(SUM(bp.pointsAwarded), 0) AS totalPoints',
                'COALESCE(SUM(t.distanceKm), 0) AS totalKm',
                'COUNT(DISTINCT DATE(t.tripDate)) AS activeDays'
            )
            ->join('u.trips', 't', 'WITH', 't.cityEdition = :ce')
            ->leftJoin('u.bonusPhotos', 'bp', 'WITH', 'bp.cityEdition = :ce AND bp.status = \'approved\'')
            ->setParameter('ce', $cityEdition)
            ->groupBy('u.id')
            ->orderBy('totalPoints', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
