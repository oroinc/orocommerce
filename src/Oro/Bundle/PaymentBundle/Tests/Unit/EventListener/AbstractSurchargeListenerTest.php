<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListener;

use Oro\Bundle\PaymentBundle\Event\CollectSurchargeEvent;
use Oro\Bundle\PaymentBundle\EventListener\AbstractSurchargeListener;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;

abstract class AbstractSurchargeListenerTest extends \PHPUnit\Framework\TestCase
{
    const AMOUNT = 100;

    /** @var SubtotalProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $provider;

    /**
     * @param CollectSurchargeEvent $event
     * @return float|int
     */
    abstract protected function getAmount(CollectSurchargeEvent $event);

    /**
     * @return AbstractSurchargeListener
     */
    abstract protected function getListener();

    protected function setUp(): void
    {
        $this->provider = $this->createMock(SubtotalProviderInterface::class);
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
        $this->getListener()->onCollectSurcharge($event);

        $this->assertEquals(self::AMOUNT, $this->getAmount($event));
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
        $this->getListener()->onCollectSurcharge($event);

        $this->assertEquals(60, $this->getAmount($event));
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
        $this->getListener()->onCollectSurcharge($event);

        $this->assertEquals(0, $this->getAmount($event));
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
        $this->getListener()->onCollectSurcharge($event);

        $this->assertEquals(0, $this->getAmount($event));
    }
}
