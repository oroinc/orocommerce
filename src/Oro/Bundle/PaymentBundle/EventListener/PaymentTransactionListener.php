<?php

namespace Oro\Bundle\PaymentBundle\EventListener;

use Oro\Bundle\PaymentBundle\Event\TransactionCompleteEvent;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;

/**
 * Updates PaymentTransaction status
 */
class PaymentTransactionListener
{
    public function __construct(
        private PaymentStatusManager $manager
    ) {
    }

    public function onTransactionComplete(TransactionCompleteEvent $event): void
    {
        $this->manager->updateStatus($event->getTransaction());
    }
}
