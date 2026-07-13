<?php

namespace App\EventListener;

use App\Entity\BonusPhotoConfig;
use App\Entity\CityEdition;
use App\Enum\BonusChallengeEnum;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsDoctrineListener(event: Events::postPersist)]
class CityEditionCreatedListener
{
    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof CityEdition) {
            return;
        }

        $em = $args->getObjectManager();

        foreach (BonusChallengeEnum::cases() as $challenge) {
            $config = new BonusPhotoConfig($challenge, $challenge->defaultPoints());
            $config->setCityEdition($entity);
            $em->persist($config);
        }

        $em->flush();
    }
}
