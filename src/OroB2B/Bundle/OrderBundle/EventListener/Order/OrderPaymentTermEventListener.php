<?php

namespace OroB2B\Bundle\OrderBundle\EventListener\Order;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Event\OrderEvent;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class OrderPaymentTermEventListener
{
    const ACCOUNT_PAYMENT_TERM_KEY = 'accountPaymentTerm';
    const ACCOUNT_GROUP_PAYMENT_TERM_KEY = 'accountGroupPaymentTerm';

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
        $accountPaymentTerm = null;
        $accountGroupPaymentTerm = null;

        $order = $event->getOrder();
        $this->validateRelation($order);

        $account = $order->getAccount();

        if ($account) {
            $paymentTerm = $this->provider->getAccountPaymentTerm($account);
            $accountPaymentTerm = $paymentTerm ? $paymentTerm->getId() : null;
        }

        if ($account && $account->getGroup()) {
            $paymentTerm = $this->provider->getAccountGroupPaymentTerm($account->getGroup());
            $accountGroupPaymentTerm = $paymentTerm ? $paymentTerm->getId() : null;
        }

        $event->getData()->offsetSet(self::ACCOUNT_PAYMENT_TERM_KEY, $accountPaymentTerm);
        $event->getData()->offsetSet(self::ACCOUNT_GROUP_PAYMENT_TERM_KEY, $accountGroupPaymentTerm);
    }

    /**
     * This method left for the BC
     *
     * @param Order $order
     * @throws BadRequestHttpException
     */
    protected function validateRelation(Order $order)
    {
        $accountUser = $order->getAccountUser();
        if (!$accountUser) {
            return;
        }

        $account = $order->getAccount();
        if (!$account || !$accountUser->getAccount()) {
            throw new BadRequestHttpException('AccountUser without Account is not allowed');
        }

        if ($accountUser->getAccount()->getId() !== $account->getId()) {
            throw new BadRequestHttpException('AccountUser must belong to Account');
        }
    }
}
