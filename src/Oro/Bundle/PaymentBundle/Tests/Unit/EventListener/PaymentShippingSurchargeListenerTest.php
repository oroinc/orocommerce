<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListener;

use Oro\Bundle\PaymentBundle\EventListener\PaymentShippingSurchargeListener;
use Oro\Bundle\PaymentBundle\Event\CollectSurchargeEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;

class PaymentShippingSurchargeListenerTest extends \PHPUnit_Framework_TestCase
{
    const AMOUNT = 100;

    /** @var SubtotalProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    /** @var PaymentShippingSurchargeListener */
    protected $listener;

    public function setUp()
    {
        $this->provider = $this->getMock(SubtotalProviderInterface::class);
        $this->listener = new PaymentShippingSurchargeListener($this->provider);
    }

    public function testCollectSurchargeWithOneSubtotal()
    {
        $entity = new \stdClass();

        $this->provider->expects($this->once())
            ->method('isSupported')
            ->with($entity)
            ->willReturn(true);

        $subtotal = new Subtotal();
        $subtotal
            ->setCurrency('USD')
            ->setAmount(self::AMOUNT)
            ->setType('type')
            ->setOperation(Subtotal::OPERATION_ADD);

        $this->provider->expects($this->once())
            ->method('getSubtotal')
            ->with($entity)
            ->willReturn($subtotal);

        $event = new CollectSurchargeEvent($entity);
        $this->listener->onCollectSurcharge($event);

        $this->assertEquals(self::AMOUNT, $event->getSurchargeModel()->getShippingAmount());
    }

    public function testCollectSurchargeWithFewSubtotals()
    {
        $entity = new \stdClass();

        $this->provider->expects($this->once())
            ->method('isSupported')
            ->with($entity)
            ->willReturn(true);

        $subtotal1 = new Subtotal();
        $subtotal1
            ->setCurrency('USD')
            ->setAmount(100)
            ->setType('type')
            ->setOperation(Subtotal::OPERATION_ADD);

        $subtotal2 = new Subtotal();
        $subtotal2
            ->setCurrency('USD')
            ->setAmount(40)
            ->setType('type')
            ->setOperation(Subtotal::OPERATION_SUBTRACTION);

        $this->provider->expects($this->once())
            ->method('getSubtotal')
            ->with($entity)
            ->willReturn([$subtotal1, $subtotal2]);

        $event = new CollectSurchargeEvent($entity);
        $this->listener->onCollectSurcharge($event);

        $this->assertEquals(60, $event->getSurchargeModel()->getShippingAmount());
    }

    public function testCollectSurchargeWithEmptySubtotals()
    {
        $entity = new \stdClass();

        $this->provider->expects($this->once())
            ->method('isSupported')
            ->with($entity)
            ->willReturn(true);

        $this->provider->expects($this->once())
            ->method('getSubtotal')
            ->with($entity)
            ->willReturn([]);

        $event = new CollectSurchargeEvent($entity);
        $this->listener->onCollectSurcharge($event);

        $this->assertEquals(0, $event->getSurchargeModel()->getShippingAmount());
    }

    public function testCollectSurchargeUnsupportedEntity()
    {
        $entity = new \stdClass();

        $this->provider->expects($this->once())
            ->method('isSupported')
            ->with($entity)
            ->willReturn(false);

        $this->provider->expects($this->never())
            ->method('getSubtotal');

        $event = new CollectSurchargeEvent($entity);
        $this->listener->onCollectSurcharge($event);

        $this->assertEquals(0, $event->getSurchargeModel()->getShippingAmount());
    }
}
