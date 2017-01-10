<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;

class OrderPaymentTermEventListener
{
    const ACCOUNT_PAYMENT_TERM_KEY = 'customerPaymentTerm';
    const ACCOUNT_GROUP_PAYMENT_TERM_KEY = 'customerGroupPaymentTerm';

    /** @var PaymentTermProvider */
    protected $provider;

    /**
     * @param PaymentTermProvider $provider
     */
    public function __construct(PaymentTermProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param OrderEvent $event
     */
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
     * @param Order $order
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
