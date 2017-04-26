<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper;

use Oro\Bundle\InfinitePayBundle\Action\Mapper\ActivationRequestMapper;
use Oro\Bundle\InfinitePayBundle\Action\Provider\ClientDataProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceDataProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceDataProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\Provider\OrderTotalProviderInterface;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ActivateOrder;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ClientData;
use Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper\Helper\OrderTotalProviderHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * {@inheritdoc}
 */
class ActivationRequestMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClientDataProvider
     */
    protected $clientDataProvider;

    /**
     * @var OrderTotalProviderInterface
     */
    protected $orderTotalProvider = 'test_order_id';

    /**
     * @var InvoiceDataProviderInterface
     */
    protected $invoiceDataProvider;

    protected $orderId;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->clientDataProvider = $this
            ->getMockBuilder(ClientDataProvider::class)->disableOriginalConstructor()->getMock();
        $clientData = (new ClientData())->setClientRef('client_ref')->setSecurityCd('security_cd');
        $this->clientDataProvider->method('getClientData')->willReturn($clientData);

        $this->orderTotalProvider = (new OrderTotalProviderHelper())->getOrderTotalProvider();

        $this->invoiceDataProvider = $this
            ->getMockBuilder(InvoiceDataProvider::class)->disableOriginalConstructor()->getMock();
    }

    public function testCreateRequestFromOrder()
    {
        $activateRequestMapper = new ActivationRequestMapper(
            $this->clientDataProvider,
            $this->orderTotalProvider,
            $this->invoiceDataProvider
        );

        /** @var InfinitePayConfigInterface|PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->createMock(InfinitePayConfigInterface::class);

        $order = new Order();
        $order->setIdentifier($this->orderId);
        $activateOrder = $activateRequestMapper->createRequestFromOrder($order, $config, []);
        $this->assertInstanceOf(ActivateOrder::class, $activateOrder);
        $requestCapture = $activateOrder->getRequest();
        $this->assertEquals($this->orderId, $requestCapture->getOrderData()->getOrderId());
    }
}
