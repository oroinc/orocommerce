<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\ORM\OrderStatusListener;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderStub;

class OrderStatusListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrderConfigurationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationProvider;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRepository;

    /** @var OrderStatusListener */
    private $listener;

    protected function setUp(): void
    {
        $this->configurationProvider = $this->createMock(OrderConfigurationProviderInterface::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(ExtendHelper::buildEnumValueClassName(Order::INTERNAL_STATUS_CODE))
            ->willReturn($this->entityRepository);

        $this->listener = new OrderStatusListener($this->configurationProvider, $doctrine);
    }

    public function testPrePersistWhenInternalStatusIsNotSetYet(): void
    {
        $order = new OrderStub();
        $this->configurationProvider->expects(self::once())
            ->method('getNewOrderInternalStatus')
            ->with(self::identicalTo($order))
            ->willReturn(OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN);

        $defaultInternalStatus = new TestEnumValue(OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN, 'Open');
        $this->entityRepository->expects(self::once())
            ->method('find')
            ->with(OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN)
            ->willReturn($defaultInternalStatus);

        $this->listener->prePersist($order);
        self::assertEquals($defaultInternalStatus, $order->getInternalStatus());
    }

    public function testPrePersistWhenInternalStatusIsNotSetYetAndNoDefaultStatusIsnotConfigured(): void
    {
        $order = new OrderStub();
        $this->configurationProvider->expects(self::once())
            ->method('getNewOrderInternalStatus')
            ->with(self::identicalTo($order))
            ->willReturn(null);

        $this->entityRepository->expects(self::never())
            ->method('find');

        $this->listener->prePersist($order);
        self::assertNull($order->getInternalStatus());
    }

    public function testPrePersistWhenInternalStatusIsAlreadySet(): void
    {
        $order = new OrderStub();
        $order->setInternalStatus(new TestEnumValue('another', 'Another'));
        $orderStatus = $order->getInternalStatus();

        $this->configurationProvider->expects(self::never())
            ->method('getNewOrderInternalStatus');
        $this->entityRepository->expects(self::never())
            ->method('find');

        $this->listener->prePersist($order);
        self::assertSame($orderStatus, $order->getInternalStatus());
    }
}
