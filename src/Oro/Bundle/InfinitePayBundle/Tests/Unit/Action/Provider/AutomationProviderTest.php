<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Provider;

use Oro\Bundle\InfinitePayBundle\Action\Provider\AutomationProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceDataProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceDataProviderInterface;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfig;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestReservation;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ReserveOrder;
use Oro\Bundle\OrderBundle\Entity\Order;
use PHPUnit_Framework_MockObject_MockObject;

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
        /** @var InfinitePayConfig $config */
        $config = new InfinitePayConfig(
            [
                InfinitePayConfig::AUTO_CAPTURE_KEY => true,
                InfinitePayConfig::AUTO_ACTIVATE_KEY => false
            ]
        );

        $automationProvider = new AutomationProvider($this->invoiceDataProvider);
        $reserveOrder = $this->getReserveOrder();
        $actualReserveOrder = $automationProvider->setAutomation($reserveOrder, new Order(), $config);
        $this->assertEquals('1', $actualReserveOrder->getREQUEST()->getOrderData()->getAutoCapture());
        $this->assertNull($actualReserveOrder->getREQUEST()->getOrderData()->getAutoActivate());
    }

    public function testCaptureOnActivationOn()
    {
        /** @var InfinitePayConfig $config */
        $config = new InfinitePayConfig(
            [
                InfinitePayConfig::AUTO_CAPTURE_KEY => true,
                InfinitePayConfig::AUTO_ACTIVATE_KEY => true
            ]
        );

        $automationProvider = new AutomationProvider($this->invoiceDataProvider);
        $reserveOrder = $this->getReserveOrder();
        $actualReserveOrder = $automationProvider->setAutomation($reserveOrder, new Order(), $config);
        $this->assertEquals('1', $actualReserveOrder->getREQUEST()->getOrderData()->getAutoCapture());
        $this->assertEquals('1', $actualReserveOrder->getREQUEST()->getOrderData()->getAutoActivate());
    }

    public function testCaptureOffActivationOff()
    {
        /** @var InfinitePayConfig $config */
        $config = new InfinitePayConfig(
            [
                InfinitePayConfig::AUTO_CAPTURE_KEY => false,
                InfinitePayConfig::AUTO_ACTIVATE_KEY => false
            ]
        );

        $automationProvider = new AutomationProvider($this->invoiceDataProvider);
        $reserveOrder = $this->getReserveOrder();
        $actualReserveOrder = $automationProvider->setAutomation($reserveOrder, new Order(), $config);
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
