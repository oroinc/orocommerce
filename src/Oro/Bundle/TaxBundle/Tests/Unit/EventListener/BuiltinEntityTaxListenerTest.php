<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\EventListener\BuiltinEntityTaxListener;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Provider\BuiltInTaxProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Oro\Component\Testing\Unit\EntityTrait;

class BuiltinEntityTaxListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var BuiltInTaxProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $taxProvider;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $orderMetadata;

    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $taxValueMetadata;

    /** @var BuiltinEntityTaxListener */
    private $listener;

    protected function setUp(): void
    {
        $this->taxProvider = $this->createMock(BuiltInTaxProvider::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->orderMetadata = $this->createMock(ClassMetadata::class);
        $this->taxValueMetadata = $this->createMock(ClassMetadata::class);

        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $taxProviderRegistry->expects($this->any())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturnCallback(function (string $class) {
                switch ($class) {
                    case Order::class:
                        return $this->orderMetadata;
                    case TaxValue::class:
                        return $this->taxValueMetadata;
                    default:
                        throw new \RuntimeException();
                }
            });

        $this->listener = new BuiltinEntityTaxListener($taxProviderRegistry);
    }

    public function testPrePersist()
    {
        $order = new Order();
        $taxValue = new TaxValue();
        $event = new LifecycleEventArgs($order, $this->entityManager);

        $this->orderMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->willReturn([]);

        $this->taxProvider->expects($this->once())
            ->method('createTaxValue')
            ->with($order)
            ->willReturn($taxValue);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($taxValue);

        $this->listener->prePersist($order, $event);
    }

    public function testPrePersistWithDisabledTaxation()
    {
        $order = new Order();
        $event = new LifecycleEventArgs($order, $this->entityManager);

        $this->orderMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->willReturn([]);

        $this->taxProvider->expects($this->once())
            ->method('createTaxValue')
            ->with($order)
            ->willThrowException(new TaxationDisabledException());

        $this->listener->prePersist($order, $event);
    }

    public function testPrePersistOrderWithId()
    {
        $order = new Order();
        $event = new LifecycleEventArgs($order, $this->entityManager);

        $this->orderMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->willReturn([1]);

        $this->taxProvider->expects($this->never())
            ->method('createTaxValue');

        $this->listener->prePersist($order, $event);
    }

    public function testPrePersistWithNotBuiltinProvider()
    {
        $taxProvider = $this->createMock(TaxProviderInterface::class);

        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $taxProviderRegistry->expects($this->any())
            ->method('getEnabledProvider')
            ->willReturn($taxProvider);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->never())
            ->method('getClassMetadata');

        $order = new Order();
        $event = new LifecycleEventArgs($order, $entityManager);

        $listener = new BuiltinEntityTaxListener($taxProviderRegistry);
        $listener->prePersist($order, $event);
    }

    public function testPostPersistWithoutTaxValue()
    {
        $order = new Order();
        $event = new LifecycleEventArgs($order, $this->entityManager);

        $this->entityManager->expects($this->never())
            ->method('getUnitOfWork');

        $this->listener->postPersist($order, $event);
    }

    public function testPostPersist()
    {
        $order = new Order();
        $taxValue = new TaxValue();
        $event = new LifecycleEventArgs($order, $this->entityManager);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($taxValue);

        $this->taxProvider->expects($this->once())
            ->method('createTaxValue')
            ->with($order)
            ->willReturn($taxValue);

        $this->listener->prePersist($order, $event);

        $orderId = 1;
        $this->setValue($order, 'id', $orderId);

        $uow = $this->createMock(UnitOfWork::class);

        $uow->expects($this->once())
            ->method('propertyChanged')
            ->with($taxValue, 'entityId', null, $orderId);

        $uow->expects($this->once())
            ->method('scheduleExtraUpdate')
            ->with($taxValue, ['entityId' => [null, $orderId]]);

        $uow->expects($this->once())
            ->method('recomputeSingleEntityChangeSet')
            ->with($this->taxValueMetadata, $taxValue);

        $this->entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->orderMetadata->expects($this->any())
            ->method('getIdentifierValues')
            ->willReturn([$orderId]);

        $event = new LifecycleEventArgs($order, $this->entityManager);
        $this->listener->postPersist($order, $event);

        $this->assertEquals($orderId, $taxValue->getEntityId());

        // Check that listener state cleared properly
        $this->listener->postPersist($order, $event);
    }
}
