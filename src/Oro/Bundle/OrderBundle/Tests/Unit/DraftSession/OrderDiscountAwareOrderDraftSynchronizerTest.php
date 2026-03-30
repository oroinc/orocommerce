<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\DraftSession;

use Oro\Bundle\OrderBundle\DraftSession\OrderDiscountAwareOrderDraftSynchronizer;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Tests\Unit\Stub\OrderStub;
use PHPUnit\Framework\TestCase;

final class OrderDiscountAwareOrderDraftSynchronizerTest extends TestCase
{
    private OrderDiscountAwareOrderDraftSynchronizer $synchronizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->synchronizer = new OrderDiscountAwareOrderDraftSynchronizer();
    }

    public function testSupportsOrderClass(): void
    {
        self::assertTrue($this->synchronizer->supports(Order::class));
    }

    public function testDoesNotSupportOtherClass(): void
    {
        self::assertFalse($this->synchronizer->supports(\stdClass::class));
    }

    public function testSynchronizeFromDraftAddsNewDiscount(): void
    {
        $sourceDiscount = new OrderDiscount();
        $sourceDiscount->setType(OrderDiscount::TYPE_AMOUNT);
        $sourceDiscount->setDescription('Volume discount');
        $sourceDiscount->setPercent(10.0);
        $sourceDiscount->setAmount(50.0);

        $draft = new OrderStub();
        $draft->addDiscount($sourceDiscount);

        $entity = new OrderStub();

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        $discounts = $entity->getDiscounts();
        self::assertCount(1, $discounts);
        $newDiscount = $discounts->last();
        self::assertEquals(OrderDiscount::TYPE_AMOUNT, $newDiscount->getType());
        self::assertEquals('Volume discount', $newDiscount->getDescription());
        self::assertEquals(10.0, $newDiscount->getPercent());
        self::assertEquals(50.0, $newDiscount->getAmount());
    }

    public function testSynchronizeFromDraftUpdatesExistingDiscount(): void
    {
        $sourceDiscount = new OrderDiscount();
        $sourceDiscount->setType(OrderDiscount::TYPE_PERCENT);
        $sourceDiscount->setDescription('Updated discount');
        $sourceDiscount->setPercent(20.0);
        $sourceDiscount->setAmount(100.0);

        $existingDiscount = new OrderDiscount();
        $existingDiscount->setType(OrderDiscount::TYPE_AMOUNT);
        $existingDiscount->setDescription('Old discount');
        $existingDiscount->setPercent(5.0);
        $existingDiscount->setAmount(25.0);

        $draft = new OrderStub();
        $draft->addDiscount($sourceDiscount);

        $entity = new OrderStub();
        $entity->addDiscount($existingDiscount);

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        $discounts = $entity->getDiscounts();
        self::assertCount(1, $discounts);
        self::assertSame($existingDiscount, $discounts->first());
        self::assertEquals(OrderDiscount::TYPE_PERCENT, $existingDiscount->getType());
        self::assertEquals('Updated discount', $existingDiscount->getDescription());
        self::assertEquals(20.0, $existingDiscount->getPercent());
        self::assertEquals(100.0, $existingDiscount->getAmount());
    }

    public function testSynchronizeFromDraftRemovesExtraDiscounts(): void
    {
        $discount1 = new OrderDiscount();
        $discount1->setAmount(10.0);

        $discount2 = new OrderDiscount();
        $discount2->setAmount(20.0);

        $draft = new OrderStub();
        // No discounts in draft

        $entity = new OrderStub();
        $entity->addDiscount($discount1);
        $entity->addDiscount($discount2);

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertCount(0, $entity->getDiscounts());
    }
}
