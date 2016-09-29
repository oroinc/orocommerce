<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper;

use Oro\Bundle\InfinitePayBundle\Action\Mapper\CaptureRequestMapper;
use Oro\Bundle\InfinitePayBundle\Action\Provider\ClientDataProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\OrderTotalProviderInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CaptureOrder;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ClientData;
use Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper\Helper\OrderTotalProviderHelper;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * {@inheritdoc}
 */
class CaptureRequestMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClientDataProvider
     */
    protected $clientDataProvider;

    /**
     * @var OrderTotalProviderInterface
     */
    protected $orderTotalProvider = 'test_order_id';
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
    }

    public function test()
    {
        $captureRequestMapper = new CaptureRequestMapper($this->clientDataProvider, $this->orderTotalProvider);
        $order = new Order();
        $order->setIdentifier($this->orderId);
        $captureOrder = $captureRequestMapper->createRequestFromOrder($order);
        $this->assertInstanceOf(CaptureOrder::class, $captureOrder);
        $requestCapture = $captureOrder->getREQUEST();
        $this->assertEquals($this->orderId, $requestCapture->getOrderData()->getOrderId());
    }
}
