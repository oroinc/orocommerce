<?php

declare(strict_types=1);

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener\DraftSession;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Event\SkipOrderTaxRecalculationEvent;
use Oro\Bundle\TaxBundle\EventListener\DraftSession\SkipOrderTaxRecalculationEntityDraftAwareEventListener;
use Oro\Bundle\TaxBundle\Model\Taxable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SkipOrderTaxRecalculationEntityDraftAwareEventListenerTest extends TestCase
{
    private UnitOfWork&MockObject $unitOfWork;

    private SkipOrderTaxRecalculationEntityDraftAwareEventListener $listener;

    #[\Override]
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

        $this->listener = new SkipOrderTaxRecalculationEntityDraftAwareEventListener($doctrine);
    }

    public function testOnSkipOrderTaxRecalculationWhenNoUnitOfWork(): void
    {
        $taxable = new Taxable();
        $taxable->setClassName(Order::class)
            ->setIdentifier(1);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn(null);

        $listener = new SkipOrderTaxRecalculationEntityDraftAwareEventListener($doctrine);

        $event = new SkipOrderTaxRecalculationEvent($taxable);
        $listener->onSkipOrderTaxRecalculation($event);

        self::assertFalse($event->isSkipOrderTaxRecalculation());
    }

    /**
     * @dataProvider onSkipOrderTaxRecalculationWrongEntityDataProvider
     */
    public function testOnSkipOrderTaxRecalculationWrongEntity(mixed $entity): void
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

    public function onSkipOrderTaxRecalculationWrongEntityDataProvider(): array
    {
        return [
            ['entity' => false],
            ['entity' => new \stdClass()],
        ];
    }

    public function testOnSkipOrderTaxRecalculationWithoutDraftSessionUuid(): void
    {
        $order = new Order();

        $taxable = new Taxable();
        $taxable->setClassName(Order::class)
            ->setIdentifier(1);

        $this->unitOfWork->expects(self::once())
            ->method('tryGetById')
            ->with($taxable->getIdentifier(), $taxable->getClassName())
            ->willReturn($order);

        $event = new SkipOrderTaxRecalculationEvent($taxable);
        $this->listener->onSkipOrderTaxRecalculation($event);

        self::assertFalse($event->isSkipOrderTaxRecalculation());
    }

    public function testOnSkipOrderTaxRecalculationWithDraftSessionUuid(): void
    {
        $order = new Order();
        $order->setDraftSessionUuid('test-uuid');

        $taxable = new Taxable();
        $taxable->setClassName(Order::class)
            ->setIdentifier(1);

        $this->unitOfWork->expects(self::once())
            ->method('tryGetById')
            ->with($taxable->getIdentifier(), $taxable->getClassName())
            ->willReturn($order);

        $event = new SkipOrderTaxRecalculationEvent($taxable);
        $this->listener->onSkipOrderTaxRecalculation($event);

        self::assertTrue($event->isSkipOrderTaxRecalculation());
    }
}
