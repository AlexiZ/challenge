<?php

namespace App\EventListener;

use App\Entity\Trip;
use App\Service\TripPointsCalculator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class TripPointsListener
{
    public function __construct(
        private readonly TripPointsCalculator $calculator,
    ) {}

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->computePoints($args);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->computePoints($args);
    }

    private function computePoints(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Trip) {
            return;
        }

        $entity->setPointsGenerated($this->calculator->calculateTripPoints($entity));
    }
}
