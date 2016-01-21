<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

class AccountGroupChangesListener
{
    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof AccountGroup) {
                $x = 0;
            }
        }
    }
}
