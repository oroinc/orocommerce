<?php

namespace OroB2B\Bundle\OrderBundle\Layout\DataProvider;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\PaymentBundle\Provider\PaymentStatusProvider;

class OrderPaymentStatusProvider
{
    /**
     * @var PaymentStatusProvider
     */
    protected $paymentStatusProvider;

    /**
     * @param PaymentStatusProvider $paymentStatusProvider
     */
    public function __construct(PaymentStatusProvider $paymentStatusProvider)
    {
        $this->paymentStatusProvider = $paymentStatusProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $order = $context->data()->get('order');
        return $this->paymentStatusProvider->getPaymentStatus($order);
    }
}
