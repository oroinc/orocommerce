<?php

namespace Oro\Bundle\OrderBundle\Layout\DataProvider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;

/**
 * Layout data provider of an order payment status.
 */
class OrderPaymentStatusProvider
{
    /**
     * @var PaymentStatusProviderInterface
     */
    protected $paymentStatusProvider;

    private ?PaymentStatusManager $paymentStatusManager = null;

    public function __construct(PaymentStatusProviderInterface $paymentStatusProvider)
    {
        $this->paymentStatusProvider = $paymentStatusProvider;
    }

    public function setPaymentStatusManager(?PaymentStatusManager $paymentStatusManager): void
    {
        $this->paymentStatusManager = $paymentStatusManager;
    }

    /**
     * @param Order $order
     *
     * @return string
     */
    public function getPaymentStatus(Order $order)
    {
        // BC layer.
        if (!$this->paymentStatusManager) {
            return $this->paymentStatusProvider->getPaymentStatus($order);
        }

        return (string) $this->paymentStatusManager->getPaymentStatus($order);
    }
}
