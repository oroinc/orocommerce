<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;

use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\EventListener\EntityTaxListener;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityTaxListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var TaxManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $taxManager;

    /** @var EntityTaxListener */
    protected $listener;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject */
    protected $metadata;

    protected function setUp()
    {
        $this->taxManager = $this->createMock('Oro\Bundle\TaxBundle\Manager\TaxManager');
        $this->entityManager = $this->createMock('Doctrine\ORM\EntityManager');
        $this->metadata = $this->createMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');

        $this->entityManager->expects($this->any())->method('getClassMetadata')->willReturn($this->metadata);

        $this->listener = new EntityTaxListener($this->taxManager);
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

        $this->taxManager->expects($this->exactly((int) $expected))
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

        $this->taxManager->expects($this->never())->method('createTaxValue');
        $this->metadata->expects($this->exactly((int) $expected))->method('getIdentifierValues')->willReturn([1]);

        $this->listener->prePersist($order, $event);

        $this->assertEquals(0, $taxValue->getEntityId());
    }

    /**
     * @param null|bool $state
     * @param bool $expected
     *
     * @dataProvider stateProvider
     */
    public function testPreRemove($state, $expected)
    {
        if (null !== $state) {
            $this->listener->setEnabled($state);
        }

        $order = new Order();
        $this->taxManager->expects($this->exactly((int) $expected))->method('removeTax')->with($order);

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
        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock('Doctrine\ORM\EntityManager');

        /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $metadata */
        $metadata = $this->createMock('Doctrine\ORM\Mapping\ClassMetadata');

        $entityManager->expects($this->any())->method('getClassMetadata')->willReturn($metadata);

        $event = new LifecycleEventArgs($order, $entityManager);

        $this->taxManager->expects($this->once())->method('createTaxValue')->with($order)->willReturn($taxValue);

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

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock('Doctrine\ORM\EntityManager');

        /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $metadata */
        $metadata = $this->createMock('Doctrine\ORM\Mapping\ClassMetadata');

        $entityManager->expects($this->any())->method('getClassMetadata')->willReturn($metadata);

        // Test
        $uow = $this->createMock('Doctrine\ORM\UnitOfWork');

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
        $this->taxManager->expects($this->never())->method('getTaxValue');
        $this->taxManager->expects($this->never())->method('loadTax');
        $this->taxManager->expects($this->never())->method('getTax');
        $this->taxManager->expects($this->never())->method('saveTax');
        $this->metadata->expects($this->never())->method('getIdentifierValues');

        $this->listener->preFlush($taxValue, $event);
    }

    public function testPreFlushOnEntityWithoutSavedTaxValue()
    {
        $orderId = 1;
        /** @var Order $order */
        $order = $this->getEntity(Order::class, ['id' => $orderId]);

        $event = new PreFlushEventArgs($this->entityManager);

        $taxValue = new TaxValue();
        $this->taxManager->expects($this->once())
            ->method('getTaxValue')
            ->with($order)
            ->willReturn($taxValue);

        $this->taxManager->expects($this->never())
            ->method('loadTax');

        $this->taxManager->expects($this->never())
            ->method('getTax');

        $this->taxManager->expects($this->once())
            ->method('saveTax')
            ->with($order, false);

        $this->metadata->expects($this->any())->method('getIdentifierValues')->willReturn([$orderId]);

        $this->listener->preFlush($order, $event);
    }

    public function testPreFlushWithSameTaxResults()
    {
        $orderId = 1;
        /** @var Order $order */
        $order = $this->getEntity(Order::class, ['id' => $orderId]);

        $event = new PreFlushEventArgs($this->entityManager);

        $taxValue = $this->getEntity(TaxValue::class, ['id' => 1]);
        $this->taxManager->expects($this->once())
            ->method('getTaxValue')
            ->with($order)
            ->willReturn($taxValue);

        $loadedResult = new Result([Result::TOTAL => ResultElement::create(0, 0)]);
        $this->taxManager->expects($this->once())
            ->method('loadTax')
            ->with($order)
            ->willReturn($loadedResult);

        $calculatedResult = new Result([Result::TOTAL => ResultElement::create(0, 0)]);
        $this->taxManager->expects($this->once())
            ->method('getTax')
            ->with($order)
            ->willReturn($calculatedResult);

        $this->taxManager->expects($this->never())
            ->method('saveTax')
            ->with($order, false);

        $this->metadata->expects($this->any())->method('getIdentifierValues')->willReturn([$orderId]);

        $this->listener->preFlush($order, $event);
    }

    public function testPreFlushWithDifferentTaxResults()
    {
        $orderId = 1;
        /** @var Order $order */
        $order = $this->getEntity(Order::class, ['id' => $orderId]);

        $event = new PreFlushEventArgs($this->entityManager);

        $taxValue = $this->getEntity(TaxValue::class, ['id' => 1]);
        $this->taxManager->expects($this->once())
            ->method('getTaxValue')
            ->with($order)
            ->willReturn($taxValue);

        $loadedResult = new Result([Result::TOTAL => ResultElement::create(0, 0)]);
        $this->taxManager->expects($this->once())
            ->method('loadTax')
            ->with($order)
            ->willReturn($loadedResult);

        $calculatedResult = new Result([Result::TOTAL => ResultElement::create(1, 1)]);
        $this->taxManager->expects($this->once())
            ->method('getTax')
            ->with($order)
            ->willReturn($calculatedResult);

        $this->taxManager->expects($this->once())
            ->method('saveTax')
            ->with($order, false);

        $this->metadata->expects($this->any())->method('getIdentifierValues')->willReturn([$orderId]);

        $this->listener->preFlush($order, $event);
    }
}
