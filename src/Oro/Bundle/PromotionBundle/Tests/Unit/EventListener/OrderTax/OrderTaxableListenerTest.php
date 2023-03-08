<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener\OrderTax;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\EventListener\OrderTax\OrderTaxableListener;
use Oro\Bundle\TaxBundle\Event\SkipOrderTaxRecalculationEvent;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Component\Testing\ReflectionUtil;

class OrderTaxableListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TaxationSettingsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $taxationSettingsProvider;

    /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject */
    private $unitOfWork;

    /** @var OrderTaxableListener */
    private $listener;

    protected function setUp(): void
    {
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);

        $this->unitOfWork = $this->createMock(UnitOfWork::class);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $this->listener = new OrderTaxableListener($doctrine, $this->taxationSettingsProvider);
    }

    public function testOnSkipOrderTaxRecalculationCalculateAfterPromotionsDisabled(): void
    {
        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(false);

        $event = new SkipOrderTaxRecalculationEvent(new Taxable());
        $this->listener->onSkipOrderTaxRecalculation($event);

        self::assertFalse($event->isSkipOrderTaxRecalculation());
    }

    /**
     * @dataProvider getOnSkipOrderTaxRecalculationWrongEntityDataProvider
     * @param object|false $entity
     */
    public function testOnSkipOrderTaxRecalculationWrongEntity(object|false $entity): void
    {
        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

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

    public function testOnSkipOrderTaxRecalculationNewOrder(): void
    {
        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $taxable = new Taxable();
        $taxable->setClassName(Order::class)
            ->setIdentifier(1);

        $this->unitOfWork->expects(self::once())
            ->method('tryGetById')
            ->with($taxable->getIdentifier(), $taxable->getClassName())
            ->willReturn(new Order());

        $event = new SkipOrderTaxRecalculationEvent($taxable);
        $this->listener->onSkipOrderTaxRecalculation($event);

        self::assertFalse($event->isSkipOrderTaxRecalculation());
    }

    public function testOnSkipOrderTaxRecalculation(): void
    {
        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $taxable = new Taxable();
        $taxable->setClassName(Order::class)
            ->setIdentifier(1);

        $order = new Order();
        ReflectionUtil::setId($order, 1);

        $this->unitOfWork->expects(self::once())
            ->method('tryGetById')
            ->with($taxable->getIdentifier(), $taxable->getClassName())
            ->willReturn($order);
        $this->unitOfWork->expects(self::once())
            ->method('getEntityChangeSet')
            ->with($order)
            ->willReturn([]);

        $event = new SkipOrderTaxRecalculationEvent($taxable);
        $this->listener->onSkipOrderTaxRecalculation($event);

        self::assertTrue($event->isSkipOrderTaxRecalculation());
    }
}
