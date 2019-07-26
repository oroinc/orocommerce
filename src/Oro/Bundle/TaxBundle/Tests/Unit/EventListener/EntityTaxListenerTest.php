<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\EventListener\EntityTaxListener;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Oro\Component\Testing\Unit\EntityTrait;

class EntityTaxListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var TaxProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $taxProvider;

    /** @var EntityTaxListener */
    protected $listener;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    protected $metadata;

    protected function setUp()
    {
        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $taxProviderRegistry->expects($this->any())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->metadata = $this->createMock(ClassMetadata::class);

        $this->entityManager->expects($this->any())->method('getClassMetadata')->willReturn($this->metadata);

        $this->listener = new EntityTaxListener($taxProviderRegistry);
        $this->listener->setEntityClass(Order::class);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->taxManager, $this->entityManager, $this->metadata);
    }

    /**
     * @return \Generator
     */
    public function stateProvider()
    {
        yield 'default' => ['state' => null, 'expected' => true];
        yield 'enabled' => ['state' => true, 'expected' => true];
        yield 'disabled' => ['state' => true, 'expected' => true];
    }

    /**
     * @param null|bool $state
     * @param bool $expected
     *
     * @dataProvider stateProvider
     */
    public function testPrePersist($state, $expected)
    {
        if (null !== $state) {
            $this->listener->setEnabled($state);
        }

        $order = new Order();
        $taxValue = new TaxValue();
        $taxValue->setEntityClass(ClassUtils::getRealClass($order));

        $event = new LifecycleEventArgs($order, $this->entityManager);

        $this->taxProvider->expects($this->exactly((int) $expected))
            ->method('createTaxValue')
            ->with($order)
            ->willReturn($taxValue);

        $this->entityManager->expects($this->exactly((int) $expected))->method('persist')->with($taxValue);

        $this->metadata->expects($this->exactly((int) $expected))->method('getIdentifierValues')->willReturn([]);

        $this->listener->prePersist($order, $event);

        $this->assertEquals(0, $taxValue->getEntityId());
    }

    /**
     * @param null|bool $state
     * @param bool $expected
     *
     * @dataProvider stateProvider
     */
    public function testPrePersistWithId($state, $expected)
    {
        if (null !== $state) {
            $this->listener->setEnabled($state);
        }

        $order = new Order();
        $taxValue = new TaxValue();
        $taxValue->setEntityClass(ClassUtils::getRealClass($order));

        $event = new LifecycleEventArgs($order, $this->entityManager);

        $this->taxProvider->expects($this->never())->method('createTaxValue');
        $this->metadata->expects($this->exactly((int) $expected))->method('getIdentifierValues')->willReturn([1]);

        $this->listener->prePersist($order, $event);

        $this->assertEquals(0, $taxValue->getEntityId());
    }

    public function testPreRemove()
    {
        $order = new Order();
        $this->taxProvider->expects($this->once())->method('removeTax')->with($order);

        $this->listener->preRemove($order);
    }

    public function testPreRemoveWithDisabledTaxesCatchException()
    {
        $order = new Order();
        $this->taxProvider->expects($this->once())
            ->method('removeTax')
            ->with($order)
            ->willThrowException(new TaxationDisabledException());

        $this->listener->preRemove($order);
    }

    /**
     * @param null|bool $state
     *
     * @dataProvider stateProvider
     */
    public function testPostPersistWithoutTaxValue($state)
    {
        if (null !== $state) {
            $this->listener->setEnabled($state);
        }

        $order = new Order();
        $event = new LifecycleEventArgs($order, $this->entityManager);

        $this->entityManager->expects($this->never())->method('getUnitOfWork');

        $this->listener->postPersist($order, $event);
    }

    /**
     * @param Order $order
     * @param TaxValue $taxValue
     */
    protected function setUpPrePersist(Order $order, TaxValue $taxValue)
    {
        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->createMock(EntityManager::class);

        /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject $metadata */
        $metadata = $this->createMock(ClassMetadata::class);

        $entityManager->expects($this->any())->method('getClassMetadata')->willReturn($metadata);

        $event = new LifecycleEventArgs($order, $entityManager);

        $this->taxProvider->expects($this->once())->method('createTaxValue')->with($order)->willReturn($taxValue);

        $entityManager->expects($this->once())->method('persist')->with($taxValue);

        $this->listener->prePersist($order, $event);
    }

    public function testPostPersist()
    {
        // Prepare listener state
        $order = new Order();
        $taxValue = new TaxValue();
        $taxValue->setEntityClass(ClassUtils::getRealClass($order));

        $this->setUpPrePersist($order, $taxValue);

        $orderId = 1;
        $this->setValue($order, 'id', $orderId);

        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->createMock(EntityManager::class);

        /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject $metadata */
        $metadata = $this->createMock(ClassMetadata::class);

        $entityManager->expects($this->any())->method('getClassMetadata')->willReturn($metadata);

        // Test
        $uow = $this->createMock(UnitOfWork::class);

        $uow->expects($this->once())
            ->method('propertyChanged')
            ->with($taxValue, 'entityId', null, $orderId);

        $uow->expects($this->once())
            ->method('scheduleExtraUpdate')
            ->with($taxValue, ['entityId' => [null, $orderId]]);

        $uow->expects($this->once())
            ->method('recomputeSingleEntityChangeSet')
            ->with($metadata, $taxValue);

        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $metadata->expects($this->any())->method('getIdentifierValues')->willReturn([$orderId]);

        $event = new LifecycleEventArgs($order, $entityManager);
        $this->listener->postPersist($order, $event);

        $this->assertEquals($orderId, $taxValue->getEntityId());
    }

    public function testPostPersistWithDisabledState()
    {
        $order = new Order();
        $taxValue = new TaxValue();
        $taxValue->setEntityClass(ClassUtils::getRealClass($order));

        $this->listener->setEnabled(true);
        $this->setUpPrePersist($order, $taxValue);

        $this->listener->setEnabled(false);
        $event = new LifecycleEventArgs($order, $this->entityManager);
        $this->entityManager->expects($this->never())->method('getClassMetadata');
        $this->entityManager->expects($this->never())->method('getUnitOfWork');

        $this->listener->postPersist($order, $event);
    }

    public function testPreFlushWithDisabledState()
    {
        $order = new Order();
        $taxValue = new TaxValue();
        $taxValue->setEntityClass(ClassUtils::getRealClass($order));
        $event = new PreFlushEventArgs($this->entityManager);

        $this->listener->setEnabled(false);

        $this->entityManager->expects($this->never())->method('getClassMetadata');
        $this->entityManager->expects($this->never())->method('getUnitOfWork');
        $this->taxProvider->expects($this->never())->method('saveTax');
        $this->metadata->expects($this->never())->method('getIdentifierValues');

        $this->listener->preFlush($taxValue, $event);
    }

    public function testPreFlush()
    {
        $order = new Order();
        $event = new PreFlushEventArgs($this->entityManager);

        $this->taxProvider->expects($this->once())
            ->method('saveTax')
            ->with($order);

        $this->metadata->expects($this->once())->method('getIdentifierValues')->willReturn([1]);

        $this->listener->preFlush($order, $event);
    }
}
