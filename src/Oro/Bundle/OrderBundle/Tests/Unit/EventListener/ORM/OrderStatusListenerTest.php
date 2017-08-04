<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\ORM\OrderStatusListener;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderStub;

use Oro\Component\Testing\Unit\EntityTrait;

class OrderStatusListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityRepository;

    /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject */
    protected $uow;

    /** @var OrderStatusListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entityRepository = $this->createMock(EntityRepository::class);
        $this->uow = $this->createMock(UnitOfWork::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects($this->any())->method('getManagerForClass')->willReturn($this->entityManager);

        $this->listener = new OrderStatusListener($this->configManager, $this->registry);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->entityRepository,
            $this->entityManager,
            $this->registry,
            $this->configManager,
            $this->listener
        );
    }

    /**
     * @param bool $expected
     * @param Order $order
     *
     * @dataProvider prePersistDataProvider
     */
    public function testPrePersist($expected, Order $order)
    {
        $lifecycleEventArgs = $this->createMock(LifecycleEventArgs::class);
        $lifecycleEventArgs->expects($this->exactly((int) $expected))
            ->method('getEntityManager')
            ->willReturn($this->entityManager);
        $this->entityManager->expects($this->exactly((int) $expected))
            ->method('getUnitOfWork')
            ->willReturn($this->uow);
        $this->entityManager->expects($this->exactly((int) $expected))
            ->method('getRepository')
            ->with(ExtendHelper::buildEnumValueClassName(Order::INTERNAL_STATUS_CODE))
            ->willReturn($this->entityRepository);
        $this->configManager->expects($this->exactly((int) $expected))
            ->method('get')
            ->with('oro_order.order_creation_new_internal_order_status')
            ->willReturn(Order::INTERNAL_STATUS_OPEN);
        $status = $this->createMock(AbstractEnumValue::class);
        $this->entityRepository->expects($this->exactly((int) $expected))
            ->method('find')
            ->with(Order::INTERNAL_STATUS_OPEN)
            ->willReturn($status);
        $this->uow->expects($this->exactly((int) $expected))
            ->method('scheduleExtraUpdate')
            ->with($order, ['internal_status' => [null, $status]]);

        $this->listener->prePersist($order, $lifecycleEventArgs);
    }

    /**
     * @return \Generator
     */
    public function prePersistDataProvider()
    {
        yield 'negative' => [
            'expected' => false,
            'order' => $this->getEntity(
                OrderStub::class,
                ['internalStatus' => $this->createMock(AbstractEnumValue::class)]
            ),
        ];

        yield 'positive' => [
            'expected' => true,
            'order' => $this->getEntity(
                OrderStub::class,
                ['internalStatus' => null]
            ),
        ];
    }
}
