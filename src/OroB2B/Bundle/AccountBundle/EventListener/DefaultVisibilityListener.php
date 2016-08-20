<?php

namespace Oro\Bundle\AccountBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;

class DefaultVisibilityListener
{
    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $this->removeIfRequired($unitOfWork, $entity);
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $this->removeIfRequired($unitOfWork, $entity);
        }
    }

    /**
     * @param UnitOfWork $unitOfWork
     * @param object $entity
     */
    protected function removeIfRequired(UnitOfWork $unitOfWork, $entity)
    {
        if ($entity instanceof VisibilityInterface) {
            if ($entity->getVisibility() === $entity::getDefault($entity->getTargetEntity())) {
                $unitOfWork->remove($entity);
            }
        }
    }
}
