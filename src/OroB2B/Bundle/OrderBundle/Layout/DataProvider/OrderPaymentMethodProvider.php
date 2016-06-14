<?php

namespace OroB2B\Bundle\OrderBundle\Layout\DataProvider;

use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

class OrderPaymentMethodProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var PaymentTransactionProvider
     */
    protected $paymentTransactionProvider;

    /**
     * @param PaymentTransactionProvider $paymentTransactionProvider
     */
    public function __construct(PaymentTransactionProvider $paymentTransactionProvider)
    {
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $order = $context->data()->get('order');
        $paymentTransaction = $this->paymentTransactionProvider->getPaymentTransaction($order);

        if (!$paymentTransaction) {
            return false;
        }

        return $paymentTransaction->getPaymentMethod();
    }
}
