<?php

namespace Oro\Bundle\PaymentBundle\EventListener;

use Oro\Bundle\PaymentBundle\Event\TransactionCompleteEvent;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;

class PaymentTransactionListener
{
    /** @var PaymentStatusManager */
    protected $manager;

    public function __construct(PaymentStatusManager $manager)
    {
        $this->manager = $manager;
    }

    public function onTransactionComplete(TransactionCompleteEvent $event)
    {
        $transaction = $event->getTransaction();
        $this->manager->updateStatus($transaction);
    }
}
