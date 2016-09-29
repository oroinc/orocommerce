<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\InfinitePayBundle\Configuration\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ReserveOrder;
use Oro\Bundle\OrderBundle\Entity\Order;

class AutomationProvider implements AutomationProviderInterface
{
    /**
     * @var InfinitePayConfigInterface
     */
    protected $config;

    /**
     * @var InvoiceDataProviderInterface
     */
    protected $invoiceDataProvider;

    public function __construct(
        InfinitePayConfigInterface $infinitePayConfig,
        InvoiceDataProviderInterface $invoiceDataProvider
    ) {
        $this->config = $infinitePayConfig;
        $this->invoiceDataProvider = $invoiceDataProvider;
    }

    public function setAutomation(ReserveOrder $reserveOrder, Order $order)
    {
        if (!$this->config->isAutoCaptureActive()) {
            return $reserveOrder;
        }
        $reserveOrder->getREQUEST()->getOrderData()->setAutoCapture('1');

        if ($this->config->isAutoActivationActive()) {
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
