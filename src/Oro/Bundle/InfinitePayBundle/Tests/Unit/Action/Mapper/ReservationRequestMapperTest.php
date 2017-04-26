<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper;

use Oro\Bundle\InfinitePayBundle\Action\Mapper\ReservationRequestMapper;
use Oro\Bundle\InfinitePayBundle\Action\Provider\ArticleListProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\Provider\ClientDataProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\ClientDataProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\Provider\DebtorDataProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceDataProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceDataProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceTotalsProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\Provider\OrderTotalProviderInterface;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ClientData;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ReserveOrder;
use Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper\Helper\ArticleListProviderHelper;
use Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper\Helper\DebtorDataProviderHelper;
use Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper\Helper\OrderTotalProviderHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * {@inheritdoc}
 */
class ReservationRequestMapperTest extends \PHPUnit_Framework_TestCase
{
    protected $userInputEmail = 'test@testemailde';
    protected $userInputLegalForm = 'eV';
    protected $orderId = 'order_id';
    /**
     * @var ClientDataProviderInterface
     */
    protected $clientDataProvider;

    /**
     * @var InvoiceDataProviderInterface
     */
    protected $invoiceDataProvider;

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

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->clientDataProvider = $this
            ->getMockBuilder(ClientDataProvider::class)->disableOriginalConstructor()->getMock();
        $clientData = (new ClientData())->setClientRef('client_ref')->setSecurityCd('security_cd');
        $this->clientDataProvider->method('getClientData')->willReturn($clientData);

        $this->invoiceDataProvider = $this
            ->getMockBuilder(InvoiceDataProvider::class)->disableOriginalConstructor()->getMock();

        $this->debtorDataProvider = (new DebtorDataProviderHelper())->getDebtorDataProvider();
        $this->orderTotalProvider = (new OrderTotalProviderHelper())->getOrderTotalProvider();
        $this->articleListProvider = (new ArticleListProviderHelper())->getArticleListProvider();
    }

    public function testCreateRequestFromOrder()
    {
        /** @var InfinitePayConfigInterface|PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->createMock(InfinitePayConfigInterface::class);

        $order = new Order();
        $order->setCurrency('EUR');
        $order->setIdentifier($this->orderId);

        $reservationRequestMapper = new ReservationRequestMapper(
            $this->clientDataProvider,
            $this->debtorDataProvider,
            $this->orderTotalProvider,
            $this->articleListProvider
        );

        $userInput = ['email' => $this->userInputEmail, 'legalForm' => $this->userInputLegalForm];
        $actualResult = $reservationRequestMapper->createRequestFromOrder($order, $config, $userInput);
        $this->assertInstanceOf(ReserveOrder::class, $actualResult);
        $actualRequest = $actualResult->getREQUEST();
        $this->assertEquals($this->userInputEmail, $actualRequest->getDebtorData()->getBdEmail());
        $this->assertEquals($this->userInputLegalForm, $actualRequest->getDebtorData()->getComOrPer());
        $this->assertEquals('0', $actualRequest->getOrderData()->getAutoActivate());
        $this->assertEquals('0', $actualRequest->getOrderData()->getAutoCapture());
        $this->assertEquals($this->orderId, $actualRequest->getOrderData()->getOrderId());
    }
}
