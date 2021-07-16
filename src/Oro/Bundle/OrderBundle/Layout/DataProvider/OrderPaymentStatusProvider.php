<?php

namespace Oro\Bundle\OrderBundle\Layout\DataProvider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;

class OrderPaymentStatusProvider
{
    /**
     * @var PaymentStatusProviderInterface
     */
    protected $paymentStatusProvider;

    public function __construct(PaymentStatusProviderInterface $paymentStatusProvider)
    {
        $this->paymentStatusProvider = $paymentStatusProvider;
    }

    /**
     * @param Order $order
     *
     * @return string
     */
    public function getPaymentStatus(Order $order)
    {
        return $this->paymentStatusProvider->getPaymentStatus($order);
    }
}
