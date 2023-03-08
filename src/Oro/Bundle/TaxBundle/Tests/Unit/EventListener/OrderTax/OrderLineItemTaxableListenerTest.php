<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener\OrderTax;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Event\SkipOrderTaxRecalculationEvent;
use Oro\Bundle\TaxBundle\EventListener\OrderTax\OrderLineItemTaxableListener;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Component\Testing\ReflectionUtil;

class OrderLineItemTaxableListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject */
    private $unitOfWork;

    /** @var OrderLineItemTaxableListener */
    private $listener;

    protected function setUp(): void
    {
        $this->unitOfWork = $this->createMock(UnitOfWork::class);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $this->listener = new OrderLineItemTaxableListener($doctrine);
    }

    /**
     * @dataProvider getOnSkipOrderTaxRecalculationWrongEntityDataProvider
     */
    public function testOnSkipOrderTaxRecalculationWrongEntity($entity): void
    {
        $taxable = new Taxable();
        $taxable->setClassName(\stdClass::class)
            ->setIdentifier(1);

        $this->unitOfWork->expects(self::once())
            ->method('tryGetById')
            ->with($taxable->getIdentifier(), $taxable->getClassName())
            ->willReturn($entity);

        $event = new SkipOrderTaxRecalculationEvent($taxable);
        $this->listener->onSkipOrderTaxRecalculation($event);

        self::assertFalse($event->isSkipOrderTaxRecalculation());
    }

    public function getOnSkipOrderTaxRecalculationWrongEntityDataProvider(): array
    {
        return [
            ['entity' => false],
            ['entity' => new \stdClass()],
        ];
    }

    public function testOnSkipOrderTaxRecalculationOrderRequiredRecalculation(): void
    {
        $lineItem = new OrderLineItem();
        $lineItem->setOrder(new Order());

        $taxable = new Taxable();
        $taxable->setClassName(OrderLineItem::class)
            ->setIdentifier(1);

        $this->unitOfWork->expects(self::once())
            ->method('tryGetById')
            ->with($taxable->getIdentifier(), $taxable->getClassName())
            ->willReturn($lineItem);

        $event = new SkipOrderTaxRecalculationEvent($taxable);
        $this->listener->onSkipOrderTaxRecalculation($event);

        self::assertFalse($event->isSkipOrderTaxRecalculation());
    }

    public function testOnSkipOrderTaxRecalculationLineItemRequiredRecalculation(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 1);

        $lineItem = new OrderLineItem();
        $lineItem->setOrder($order);

        $taxable = new Taxable();
        $taxable->setClassName(OrderLineItem::class)
            ->setIdentifier(1);

        $this->unitOfWork->expects(self::once())
            ->method('tryGetById')
            ->with($taxable->getIdentifier(), $taxable->getClassName())
            ->willReturn($lineItem);
        $this->unitOfWork->expects(self::once())
            ->method('getEntityChangeSet')
            ->with($order)
            ->willReturn([]);

        $event = new SkipOrderTaxRecalculationEvent($taxable);
        $this->listener->onSkipOrderTaxRecalculation($event);

        self::assertFalse($event->isSkipOrderTaxRecalculation());
    }

    public function testOnSkipOrderTaxRecalculation(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 1);

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 1);
        $lineItem->setOrder($order);

        $taxable = new Taxable();
        $taxable->setClassName(OrderLineItem::class)
            ->setIdentifier(1);

        $this->unitOfWork->expects(self::once())
            ->method('tryGetById')
            ->with($taxable->getIdentifier(), $taxable->getClassName())
            ->willReturn($lineItem);
        $this->unitOfWork->expects(self::exactly(2))
            ->method('getEntityChangeSet')
            ->willReturn([]);

        $event = new SkipOrderTaxRecalculationEvent($taxable);
        $this->listener->onSkipOrderTaxRecalculation($event);

        self::assertTrue($event->isSkipOrderTaxRecalculation());
    }
}
