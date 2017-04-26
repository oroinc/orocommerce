<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Mapper;

use Oro\Bundle\InfinitePayBundle\Action\Provider\ClientDataProvider;
use Oro\Bundle\InfinitePayBundle\Action\RequestMapperInterface;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ApplyTransaction;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestApplyTransaction;
use Oro\Bundle\OrderBundle\Entity\Order;

class ApplyTransactionRequestMapper implements RequestMapperInterface
{
    /**
     * @var ClientDataProvider
     */
    protected $clientDataProvider;

    public function __construct(
        ClientDataProvider $clientDataProvider
    ) {
        $this->clientDataProvider = $clientDataProvider;
    }

    /**
     * @param Order $order
     * @param InfinitePayConfigInterface $config
     * @param array $userInput
     * @return ApplyTransaction
     */
    public function createRequestFromOrder(Order $order, InfinitePayConfigInterface $config, array $userInput)
    {
        $request = new ApplyTransaction();

        $orderData = new OrderTotal();
        $orderData->setOrderId($order->getIdentifier());
        $orderData->setRefNo($userInput['ref_no']);

        $applyTransaction = new RequestApplyTransaction();
        $applyTransaction->setClientData($this->clientDataProvider->getClientData($order->getIdentifier(), $config));
        $applyTransaction->setOrderData($orderData);
        $request->setRequest($applyTransaction);

        return $request;
    }
}
