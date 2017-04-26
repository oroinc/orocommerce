<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Mapper;

use Oro\Bundle\InfinitePayBundle\Action\Provider\ClientDataProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceDataProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\Provider\OrderTotalProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\RequestMapperInterface;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ActivateOrder;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestActivation;
use Oro\Bundle\OrderBundle\Entity\Order;

class ActivationRequestMapper implements RequestMapperInterface
{
    /**
     * @var ClientDataProvider
     */
    protected $clientDataProvider;

    /**
     * @var OrderTotalProviderInterface
     */
    protected $orderTotalProvider;

    /**
     * @var InvoiceDataProviderInterface
     */
    protected $invoiceDataProvider;

    public function __construct(
        ClientDataProvider $clientDataProvider,
        OrderTotalProviderInterface $orderTotalProvider,
        InvoiceDataProviderInterface $invoiceDataProviders
    ) {
        $this->clientDataProvider = $clientDataProvider;
        $this->orderTotalProvider = $orderTotalProvider;
        $this->invoiceDataProvider = $invoiceDataProviders;
    }

    /**
     * @param Order $order
     * @param InfinitePayConfigInterface $config
     * @param array $userInput
     * @return ActivateOrder
     */
    public function createRequestFromOrder(Order $order, InfinitePayConfigInterface $config, array $userInput)
    {
        $orderId = $order->getIdentifier();
        $clientData = $this->clientDataProvider->getClientData($orderId, $config);
        $activateRequest = new RequestActivation();
        $invoiceData = $this->invoiceDataProvider->getInvoiceData($order, $config);
        $orderTotals = $this->orderTotalProvider->getOrderTotal($order);

        $activateRequest->setClientData($clientData);
        $activateRequest->setInvoiceData($invoiceData);
        $activateRequest->setOrderData($orderTotals);

        $request = new ActivateOrder();
        $request->setRequest($activateRequest);

        return $request;
    }
}
