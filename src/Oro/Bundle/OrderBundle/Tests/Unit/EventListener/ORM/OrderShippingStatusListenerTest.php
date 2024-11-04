<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\ORM\OrderShippingStatusListener;
use Oro\Bundle\OrderBundle\EventListener\ORM\OrderStatusListener;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderStub;

class OrderShippingStatusListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var OrderStatusListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->listener = new OrderShippingStatusListener($this->doctrine);
    }

    public function testPrePersistWhenListenerIsDisabled(): void
    {
        $order = new OrderStub();

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        $this->listener->setEnabled(false);
        $this->listener->prePersist($order);
        self::assertNull($order->getShippingStatus());
    }

    public function testPrePersistWhenShippingStatusIsNotSetYet(): void
    {
        $order = new OrderStub();
        $defaultShippingStatus = new TestEnumValue('test', 'Test', 'test1');

        $repository = $this->createMock(EnumOptionRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(EnumOption::class)
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('getDefaultValues')
            ->with(Order::SHIPPING_STATUS_CODE)
            ->willReturn([$defaultShippingStatus]);

        $this->listener->prePersist($order);
        self::assertSame($defaultShippingStatus, $order->getShippingStatus());
    }

    public function testPrePersistWhenShippingStatusIsNotSetYetAndNoDefaultValue(): void
    {
        $order = new OrderStub();

        $repository = $this->createMock(EnumOptionRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(EnumOption::class)
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('getDefaultValues')
            ->with(Order::SHIPPING_STATUS_CODE)
            ->willReturn([]);

        $this->listener->prePersist($order);
        self::assertNull($order->getShippingStatus());
    }

    public function testPrePersistWhenShippingStatusIsAlreadySet(): void
    {
        $shippingStatus = new TestEnumValue('test', 'Test', 'test1');
        $order = new OrderStub();
        $order->setShippingStatus($shippingStatus);

        $this->listener->prePersist($order);
        self::assertSame($shippingStatus, $order->getShippingStatus());
    }
}
