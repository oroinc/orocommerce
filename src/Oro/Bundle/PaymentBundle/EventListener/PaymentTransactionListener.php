<?php

namespace Oro\Bundle\PaymentBundle\EventListener;

use Oro\Bundle\PaymentBundle\Event\TransactionCompleteEvent;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;

class PaymentTransactionListener
{
    /** @var PaymentStatusManager */
    protected $manager;

    /**
     * @param PaymentStatusManager $manager
     */
    public function __construct(PaymentStatusManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param TransactionCompleteEvent $event
     */
    public function onTransactionComplete(TransactionCompleteEvent $event)
    {
        $transaction = $event->getTransaction();
        $this->manager->updateStatus($transaction);
    }
}
