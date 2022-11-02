<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProviderInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Find customer and customer group payment term values.
 */
class OrderPaymentTermEventListener
{
    const ACCOUNT_PAYMENT_TERM_KEY = 'customerPaymentTerm';
    const ACCOUNT_GROUP_PAYMENT_TERM_KEY = 'customerGroupPaymentTerm';

    /** @var PaymentTermProviderInterface */
    protected $provider;

    public function __construct(PaymentTermProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    public function onOrderEvent(OrderEvent $event)
    {
        $customerPaymentTerm = null;
        $customerGroupPaymentTerm = null;

        $order = $event->getOrder();
        $this->validateRelation($order);

        $customer = $order->getCustomer();

        if ($customer) {
            $paymentTerm = $this->provider->getCustomerPaymentTerm($customer);
            $customerPaymentTerm = $paymentTerm ? $paymentTerm->getId() : null;
        }

        if ($customer && $customer->getGroup()) {
            $paymentTerm = $this->provider->getCustomerGroupPaymentTerm($customer->getGroup());
            $customerGroupPaymentTerm = $paymentTerm ? $paymentTerm->getId() : null;
        }

        $event->getData()->offsetSet(self::ACCOUNT_PAYMENT_TERM_KEY, $customerPaymentTerm);
        $event->getData()->offsetSet(self::ACCOUNT_GROUP_PAYMENT_TERM_KEY, $customerGroupPaymentTerm);
    }

    /**
     * This method left for the BC
     *
     * @throws BadRequestHttpException
     */
    protected function validateRelation(Order $order)
    {
        $customerUser = $order->getCustomerUser();
        if (!$customerUser) {
            return;
        }

        $customer = $order->getCustomer();
        if (!$customer || !$customerUser->getCustomer()) {
            throw new BadRequestHttpException('CustomerUser without Customer is not allowed');
        }

        if ($customerUser->getCustomer()->getId() !== $customer->getId()) {
            throw new BadRequestHttpException('CustomerUser must belong to Customer');
        }
    }
}
