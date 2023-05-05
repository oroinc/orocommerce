<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\ORM\OrderStatusListener;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderStub;
use Oro\Component\Testing\Unit\EntityTrait;

class OrderStatusListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var OrderConfigurationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationProvider;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRepository;

    /** @var OrderStatusListener */
    private $listener;

    protected function setUp(): void
    {
        $this->entityRepository = $this->createMock(EntityRepository::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->configurationProvider = $this->createMock(OrderConfigurationProviderInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->listener = new OrderStatusListener($this->configurationProvider, $doctrine);
    }

    /**
     * @dataProvider prePersistDataProvider
     */
    public function testPrePersist(bool $expected, Order $order)
    {
        $orderStatus = $order->getInternalStatus();
        $this->entityManager->expects($this->exactly((int)$expected))
            ->method('getRepository')
            ->with(ExtendHelper::buildEnumValueClassName(Order::INTERNAL_STATUS_CODE))
            ->willReturn($this->entityRepository);
        $this->configurationProvider->expects($this->exactly((int)$expected))
            ->method('getNewOrderInternalStatus')
            ->with($order)
            ->willReturn(OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN);
        $status = new TestEnumValue('open', 'open');
        $this->entityRepository->expects($this->exactly((int)$expected))
            ->method('find')
            ->with(OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN)
            ->willReturn($status);

        $this->listener->prePersist($order);
        if ($expected) {
            $this->assertEquals($status, $order->getInternalStatus());
            $this->assertNotEquals($orderStatus, $order->getInternalStatus());
        } else {
            $this->assertSame($orderStatus, $order->getInternalStatus());
            $this->assertNotEquals($status, $order->getInternalStatus());
        }
    }

    public function prePersistDataProvider(): array
    {
        return [
            'negative' => [
                'expected' => false,
                'order' => $this->getEntity(
                    OrderStub::class,
                    ['internalStatus' => new TestEnumValue(1, '')]
                ),
            ],
            'positive' => [
                'expected' => true,
                'order' => $this->getEntity(
                    OrderStub::class,
                    ['internalStatus' => null]
                ),
            ]
        ];
    }
}
