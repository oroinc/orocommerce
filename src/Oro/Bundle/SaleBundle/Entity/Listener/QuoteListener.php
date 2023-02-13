<?php

namespace Oro\Bundle\SaleBundle\Entity\Listener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\SaleBundle\Entity\Quote;

/**
 * Listens to Quote Entity events and generates qid if qid is empty
 */
class QuoteListener
{
    public function postPersist(Quote $quote, LifecycleEventArgs $event)
    {
        if ($quote->getQid()) {
            return;
        }

        $unitOfWork = $event->getEntityManager()->getUnitOfWork();

        $changeSet = [
            'qid' => [null, $quote->getId()],
        ];

        $unitOfWork->scheduleExtraUpdate($quote, $changeSet);
    }
}
