<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Provider;

use Oro\Bundle\InfinitePayBundle\Action\Provider\AutomationProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceDataProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceDataProviderInterface;
use Oro\Bundle\InfinitePayBundle\Configuration\InfinitePayConfig;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestReservation;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ReserveOrder;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * {@inheritdoc}
 */
class AutomationProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InvoiceDataProviderInterface
     */
    protected $invoiceDataProvider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->invoiceDataProvider = $this
            ->getMockBuilder(InvoiceDataProvider::class)->disableOriginalConstructor()->getMock();
    }

    public function testCaptureOnActivationOff()
    {
        $config = $this->getMockBuilder(InfinitePayConfig::class)->disableOriginalConstructor()->getMock();
        $config->method('isAutoCaptureActive')->willReturn(true);
        $config->method('isAutoActivationActive')->willReturn(false);

        $automationProvider = new AutomationProvider($config, $this->invoiceDataProvider);
        $reserveOrder = $this->getReserveOrder();
        $actualReserveOrder = $automationProvider->setAutomation($reserveOrder, new Order());
        $this->assertEquals('1', $actualReserveOrder->getREQUEST()->getOrderData()->getAutoCapture());
        $this->assertNull($actualReserveOrder->getREQUEST()->getOrderData()->getAutoActivate());
    }

    public function testCaptureOnActivationOn()
    {
        $config = $this->getMockBuilder(InfinitePayConfig::class)->disableOriginalConstructor()->getMock();
        $config->method('isAutoCaptureActive')->willReturn(true);
        $config->method('isAutoActivationActive')->willReturn(true);

        $automationProvider = new AutomationProvider($config, $this->invoiceDataProvider);
        $reserveOrder = $this->getReserveOrder();
        $actualReserveOrder = $automationProvider->setAutomation($reserveOrder, new Order());
        $this->assertEquals('1', $actualReserveOrder->getREQUEST()->getOrderData()->getAutoCapture());
        $this->assertEquals('1', $actualReserveOrder->getREQUEST()->getOrderData()->getAutoActivate());
    }

    public function testCaptureOffActivationOff()
    {
        $config = $this->getMockBuilder(InfinitePayConfig::class)->disableOriginalConstructor()->getMock();
        $config->method('isAutoCaptureActive')->willReturn(false);
        $config->method('isAutoActivationActive')->willReturn(false);

        $automationProvider = new AutomationProvider($config, $this->invoiceDataProvider);
        $reserveOrder = $this->getReserveOrder();
        $actualReserveOrder = $automationProvider->setAutomation($reserveOrder, new Order());
        $this->assertNull($actualReserveOrder->getREQUEST()->getOrderData()->getAutoCapture());
        $this->assertNull($actualReserveOrder->getREQUEST()->getOrderData()->getAutoActivate());
    }

    /**
     * @return ReserveOrder
     */
    private function getReserveOrder()
    {
        $orderData = new OrderTotal();
        $requestReservation = new RequestReservation();
        $reserverOrder = new ReserveOrder();
        $requestReservation->setOrderData($orderData);
        $reserverOrder->setRequest($requestReservation);

        return $reserverOrder;
    }
}
