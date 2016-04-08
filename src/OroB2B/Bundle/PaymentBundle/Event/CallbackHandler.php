<?php

namespace OroB2B\Bundle\PaymentBundle\Event;

use Psr\Log\LoggerAwareTrait;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;

class CallbackHandler
{
    use LoggerAwareTrait;

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
     * @param string $accessIdentifier
     * @param string $accessToken
     * @param AbstractCallbackEvent $event
     * @return Response
     */
    public function handle($accessIdentifier, $accessToken, AbstractCallbackEvent $event)
    {
        $paymentTransaction = $this->getPaymentTransaction($accessIdentifier, $accessToken);
        if (!$paymentTransaction) {
            return $event->getResponse();
        }

        $event->setPaymentTransaction($paymentTransaction);

        $this->eventDispatcher->dispatch($event->getEventName(), $event);
        $this->eventDispatcher->dispatch($event->getTypedEventName($paymentTransaction->getPaymentMethod()), $event);

        $entityManager = $this->doctrineHelper->getEntityManager($paymentTransaction);
        try {
            $entityManager->transactional(
                function () {
                }
            );
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error($e->getMessage(), $e->getTrace());
            }
        }

        return $event->getResponse();
    }

    /**
     * @param string $accessIdentifier
     * @param string $accessToken
     * @return PaymentTransaction
     */
    protected function getPaymentTransaction($accessIdentifier, $accessToken)
    {
        return $this->doctrineHelper->getEntityRepository($this->paymentTransactionClass)
            ->findOneBy(['accessIdentifier' => (string)$accessIdentifier, 'accessToken' => (string)$accessToken]);
    }
}
