<?php

namespace Oro\Bundle\PaymentBundle\EventListener\Callback;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This listener checks that transaction is still in pending status before processing by any other listeners
 * If this is duplicate callback request - redirect to failure url
 * It prevents possible double charging and any other issues when transaction has been handled already
 *
 * Listener must have higher priority than others to prevent their execution in case.
 */
class CheckCallbackRelevanceListener
{
    /**
     * @var PaymentMethodProviderInterface
     */
    private $paymentMethodProvider;

    /**
     * @var PaymentStatusProviderInterface
     */
    private $paymentStatusProvider;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    public function __construct(
        PaymentMethodProviderInterface $paymentMethodProvider,
        PaymentStatusProviderInterface $paymentStatusProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->paymentStatusProvider = $paymentStatusProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    public function onError(CallbackErrorEvent $event): void
    {
        $this->handleEvent($event);
    }

    public function onReturn(CallbackReturnEvent $event): void
    {
        $this->handleEvent($event);
    }

    private function handleEvent(AbstractCallbackEvent $event): void
    {
        $paymentTransaction = $event->getPaymentTransaction();

        if (!$paymentTransaction) {
            return;
        }

        if (false === $this->paymentMethodProvider->hasPaymentMethod($paymentTransaction->getPaymentMethod())) {
            return;
        }

        /** @var Order $order */
        $order = $this->doctrineHelper->getEntity(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );

        if (!$order) {
            $this->redirectToFailureUrl($paymentTransaction, $event);

            return;
        }

        $orderPaymentStatus = $this->paymentStatusProvider->getPaymentStatus($order);

        if ($this->isPaymentStatusAllowed($orderPaymentStatus)) {
            return;
        }

        $this->redirectToFailureUrl($paymentTransaction, $event);
    }

    protected function isPaymentStatusAllowed(string $orderPaymentStatus): bool
    {
        return $orderPaymentStatus === PaymentStatusProvider::PENDING;
    }

    private function redirectToFailureUrl(PaymentTransaction $paymentTransaction, AbstractCallbackEvent $event): void
    {
        $event->stopPropagation();

        $transactionOptions = $paymentTransaction->getTransactionOptions();
        if (!empty($transactionOptions['failureUrl'])) {
            $event->setResponse(new RedirectResponse($transactionOptions['failureUrl']));
        } else {
            $event->markFailed();
        }
    }
}
