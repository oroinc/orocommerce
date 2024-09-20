<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
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
            ->with(EnumOption::class)
            ->willReturn($this->entityRepository);

        $this->listener = new OrderStatusListener($this->configurationProvider, $doctrine);
    }

    public function testPrePersistWhenInternalStatusIsNotSetYet(): void
    {
        $order = new OrderStub();
        $openStatusId = ExtendHelper::buildEnumOptionId(
            Order::INTERNAL_STATUS_CODE,
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
        );
        $this->configurationProvider->expects(self::once())
            ->method('getNewOrderInternalStatus')
            ->with(self::identicalTo($order))
            ->willReturn($openStatusId);

        $defaultInternalStatus = new TestEnumValue(
            Order::INTERNAL_STATUS_CODE,
            'Open',
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
        );
        $this->entityRepository->expects(self::once())
            ->method('find')
            ->with($openStatusId)
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
        $order->setInternalStatus(new TestEnumValue('test', 'Test', 'test1'));
        $orderStatus = $order->getInternalStatus();

        $this->configurationProvider->expects(self::never())
            ->method('getNewOrderInternalStatus');
        $this->entityRepository->expects(self::never())
            ->method('find');

        $this->listener->prePersist($order);
        self::assertSame($orderStatus, $order->getInternalStatus());
    }
}
