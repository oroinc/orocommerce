<?php

namespace OroB2B\Bundle\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;

class CallbackHandler
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $paymentTransactionClass;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param DoctrineHelper $doctrineHelper
     * @param $paymentTransactionClass
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        DoctrineHelper $doctrineHelper,
        $paymentTransactionClass
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->doctrineHelper = $doctrineHelper;
        $this->paymentTransactionClass = (string)$paymentTransactionClass;
    }

    /**
     * @param string $transactionId
     * @param AbstractCallbackEvent $event
     * @return Response
     */
    public function handle($transactionId, AbstractCallbackEvent $event)
    {
        $paymentTransaction = $this->getPaymentTransaction($transactionId);
        if (!$paymentTransaction) {
            return $event->getResponse();
        }

        $event->setPaymentTransaction($paymentTransaction);

        $this->eventDispatcher->dispatch($event->getEventName(), $event);
        $this->eventDispatcher->dispatch($event->getTypedEventName($paymentTransaction->getType()), $event);

        return $event->getResponse();
    }

    /**
     * @param string $transactionId
     * @return PaymentTransaction
     */
    protected function getPaymentTransaction($transactionId)
    {
        return $this->doctrineHelper->getEntity($this->paymentTransactionClass, (int)$transactionId);
    }
}
