<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ReserveOrder;
use Oro\Bundle\OrderBundle\Entity\Order;

class AutomationProvider implements AutomationProviderInterface
{
    /**
     * @var InvoiceDataProviderInterface
     */
    protected $invoiceDataProvider;

    public function __construct(
        InvoiceDataProviderInterface $invoiceDataProvider
    ) {
        $this->invoiceDataProvider = $invoiceDataProvider;
    }

    public function setAutomation(ReserveOrder $reserveOrder, Order $order, InfinitePayConfigInterface $config)
    {
        if (!$config->isAutoCaptureEnabled()) {
            return $reserveOrder;
        }
        $reserveOrder->getREQUEST()->getOrderData()->setAutoCapture('1');

        if ($config->isAutoActivateEnabled()) {
            $reserveOrder = $this->enableAutoActivation($reserveOrder, $order);
        }

        return $reserveOrder;
    }

    /**
     * @param ReserveOrder $reservation
     * @param Order        $order
     *
     * @return ReserveOrder
     */
    private function enableAutoActivation(ReserveOrder $reservation, Order $order)
    {
        $invoiceData = $this->invoiceDataProvider->getInvoiceData($order);
        $reservation->getRequest()->getOrderData()->setAutoActivate('1');
        $reservation->getRequest()->setInvoiceData($invoiceData);

        return $reservation;
    }
}
