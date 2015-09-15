<?php

namespace OroB2B\Bundle\SaleBundle\Entity\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use OroB2B\Bundle\SaleBundle\Entity\Quote;

class QuoteListener
{
    /**
     * @param Quote $quote
     * @param LifecycleEventArgs $event
     */
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
