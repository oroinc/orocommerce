<?php

namespace OroB2B\Bundle\SaleBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

use OroB2B\Bundle\SaleBundle\Entity\Quote;

class EntitySubscriber implements EventSubscriber
{
    /**
     * @inheritdoc
     */
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::postPersist,
        ];
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $entity = $event->getObject();

        if (!$entity instanceof Quote) {
            return;
        }

        /* @var $entity Quote */

        if ($entity->getQid()) {
            return;
        }

        $entity->setQid('');
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $entity = $event->getObject();

        if (!$entity instanceof Quote) {
            return;
        }
        
        /* @var $entity Quote */

        if ($entity->getQid()) {
            return;
        }

        $unitOfWork = $event->getEntityManager()->getUnitOfWork();

        $changeSet = [
            'qid' => ['', $entity->getId()],
        ];

        $unitOfWork->scheduleExtraUpdate($entity, $changeSet);
    }
}
