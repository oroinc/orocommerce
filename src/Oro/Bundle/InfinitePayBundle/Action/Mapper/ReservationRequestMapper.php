<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Mapper;

use Oro\Bundle\InfinitePayBundle\Action\Provider\ArticleListProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\Provider\ClientDataProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\Provider\DebtorDataProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceTotalsProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\Provider\OrderTotalProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\RequestMapperInterface;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\BankData;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestReservation;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ReserveOrder;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ShippingData;
use Oro\Bundle\OrderBundle\Entity\Order;

class ReservationRequestMapper implements RequestMapperInterface
{
    /**
     * @var ClientDataProviderInterface
     */
    protected $clientDataProvider;

    /**
     * @var InvoiceTotalsProviderInterface
     */
    protected $invoiceTotalsProvider;

    /**
     * @var DebtorDataProviderInterface
     */
    protected $debtorDataProvider;

    /**
     * @var OrderTotalProviderInterface
     */
    protected $orderTotalProvider;

    /**
     * @var ArticleListProviderInterface
     */
    protected $articleListProvider;

    public function __construct(
        ClientDataProviderInterface $clientDataProvider,
        DebtorDataProviderInterface $debtorDataProvider,
        OrderTotalProviderInterface $orderTotalProvider,
        ArticleListProviderInterface $articleListProvider
    ) {
        $this->clientDataProvider = $clientDataProvider;
        $this->debtorDataProvider = $debtorDataProvider;
        $this->orderTotalProvider = $orderTotalProvider;
        $this->articleListProvider = $articleListProvider;
    }

    /**
     * @param Order $order
     * @param InfinitePayConfigInterface $config
     * @param array $userInput
     * @return ReserveOrder
     */
    public function createRequestFromOrder(Order $order, InfinitePayConfigInterface $config, array $userInput)
    {
        $orderId = $order->getIdentifier();
        $clientData = $this->clientDataProvider->getClientData($orderId, $config);
        $debtorData = $this->debtorDataProvider->getDebtorData($order);
        $debtorData->setBdEmai($userInput['email']);
        $debtorData->setComOrPer($userInput['legalForm']);

        $orderTotal = $this->orderTotalProvider->getOrderTotal($order);
        $orderTotal->setAutoCapture('0');
        $orderTotal->setAutoActivate('0');
        $orderTotal->setOrderId($orderId);

        $shippingData = $this->getShippingData();

        $articleList = $this->articleListProvider->getArticleList($order);

        $bankData = new BankData();
        $reservation = new RequestReservation();
        $reservation->setClientData($clientData);
        $reservation->setOrderData($orderTotal);
        $reservation->setDebtorData($debtorData);
        $reservation->setBankData($bankData);
        $reservation->setShippingData($shippingData);
        $reservation->setArticles($articleList);

        $request = new ReserveOrder();
        $request->setRequest($reservation);

        return $request;
    }

    /**
     * @return ShippingData
     */
    private function getShippingData()
    {
        $shippingData = new ShippingData();
        $shippingData->setUseBillData('1');

        return $shippingData;
    }
}
