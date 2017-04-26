<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Mapper;

use Oro\Bundle\InfinitePayBundle\Action\Provider\ClientDataProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\OrderTotalProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\RequestMapperInterface;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CaptureOrder;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestCapture;
use Oro\Bundle\OrderBundle\Entity\Order;

class CaptureRequestMapper implements RequestMapperInterface
{
    /**
     * @var ClientDataProvider
     */
    protected $clientDataProvider;

    /**
     * @var OrderTotalProviderInterface
     */
    protected $orderTotalProvider;

    public function __construct(
        ClientDataProvider $clientDataProvider,
        OrderTotalProviderInterface $orderTotalProvider
    ) {
        $this->clientDataProvider = $clientDataProvider;
        $this->orderTotalProvider = $orderTotalProvider;
    }

    /**
     * @param Order $order
     * @param InfinitePayConfigInterface $config
     * @param array $userInput
     * @return CaptureOrder
     */
    public function createRequestFromOrder(Order $order, InfinitePayConfigInterface $config, array $userInput = [])
    {
        $captureRequest = new RequestCapture();
        $orderId = $order->getIdentifier();

        $orderTotal = $this->orderTotalProvider->getOrderTotal($order);
        $orderTotal->setOrderId($orderId);

        $captureRequest->setClientData($this->clientDataProvider->getClientData($orderId, $config));
        $captureRequest->setOrderData($orderTotal);

        $request = new CaptureOrder();
        $request->setRequest($captureRequest);

        return $request;
    }

    /**
     * @param Order $order
     */
    public function getInvoiceData(Order $order)
    {
    }
}
