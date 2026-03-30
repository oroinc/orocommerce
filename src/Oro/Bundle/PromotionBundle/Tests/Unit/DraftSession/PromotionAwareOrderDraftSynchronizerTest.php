<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Tests\Unit\DraftSession;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\DraftSession\Factory\OrderLineItemDraftFactory;
use Oro\Bundle\OrderBundle\DraftSession\OrderLineItemDraftSynchronizer;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\DraftSession\PromotionAwareOrderDraftSynchronizer;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order as OrderStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PromotionAwareOrderDraftSynchronizerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntityManagerInterface&MockObject $entityManager;
    private OrderLineItemDraftFactory&MockObject $orderLineItemDraftFactory;
    private OrderLineItemDraftSynchronizer&MockObject $orderLineItemDraftSynchronizer;
    private PromotionAwareOrderDraftSynchronizer $synchronizer;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->orderLineItemDraftFactory = $this->createMock(OrderLineItemDraftFactory::class);
        $this->orderLineItemDraftSynchronizer = $this->createMock(OrderLineItemDraftSynchronizer::class);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->synchronizer = new PromotionAwareOrderDraftSynchronizer(
            $this->doctrine,
            $this->orderLineItemDraftFactory,
            $this->orderLineItemDraftSynchronizer,
        );
    }

    public function testSupportsOrderClass(): void
    {
        self::assertTrue($this->synchronizer->supports(Order::class));
    }

    public function testDoesNotSupportOtherClass(): void
    {
        self::assertFalse($this->synchronizer->supports(\stdClass::class));
    }

    public function testSynchronizeFromDraftWithNoAppliedPromotions(): void
    {
        $draft = new OrderStub();
        ReflectionUtil::setId($draft, 100);

        $entity = new OrderStub();
        ReflectionUtil::setId($entity, 200);

        $this->entityManager->expects(self::never())
            ->method('persist');

        $this->entityManager->expects(self::never())
            ->method('remove');

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertCount(0, $entity->getAppliedPromotions());
    }

    public function testSynchronizeFromDraftAddsNewAppliedPromotion(): void
    {
        $sourceAppliedPromotion = new AppliedPromotion();
        $sourceAppliedPromotion->setActive(true);
        $sourceAppliedPromotion->setRemoved(false);
        $sourceAppliedPromotion->setType('order');
        $sourceAppliedPromotion->setSourcePromotionId(100);
        $sourceAppliedPromotion->setPromotionName('Spring Sale');
        $sourceAppliedPromotion->setConfigOptions(['discount' => '10%']);
        $sourceAppliedPromotion->setPromotionData(['rule' => 'buy_x_get_y']);

        $draft = new OrderStub();
        ReflectionUtil::setId($draft, 200);
        $draft->addAppliedPromotion($sourceAppliedPromotion);

        $entity = new OrderStub();
        ReflectionUtil::setId($entity, 300);

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(AppliedPromotion::class));

        self::assertCount(0, $entity->getAppliedPromotions());

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertCount(1, $entity->getAppliedPromotions());

        $addedPromotion = $entity->getAppliedPromotions()->first();
        self::assertNotSame($sourceAppliedPromotion, $addedPromotion);
        self::assertTrue($addedPromotion->isActive());
        self::assertFalse($addedPromotion->isRemoved());
        self::assertEquals('order', $addedPromotion->getType());
        self::assertEquals(100, $addedPromotion->getSourcePromotionId());
        self::assertEquals('Spring Sale', $addedPromotion->getPromotionName());
        self::assertSame($entity, $addedPromotion->getOrder());
    }

    public function testSynchronizeFromDraftUpdatesExistingAppliedPromotion(): void
    {
        $sourceAppliedPromotion = new AppliedPromotion();
        $sourceAppliedPromotion->setActive(true);
        $sourceAppliedPromotion->setRemoved(false);
        $sourceAppliedPromotion->setType('order');
        $sourceAppliedPromotion->setSourcePromotionId(100);
        $sourceAppliedPromotion->setPromotionName('Updated Spring Sale');
        $sourceAppliedPromotion->setConfigOptions(['discount' => '20%']);

        $draft = new OrderStub();
        ReflectionUtil::setId($draft, 200);
        $draft->addAppliedPromotion($sourceAppliedPromotion);

        $targetAppliedPromotion = new AppliedPromotion();
        $targetAppliedPromotion->setActive(false);
        $targetAppliedPromotion->setSourcePromotionId(100);
        $targetAppliedPromotion->setPromotionName('Old Name');

        $entity = new OrderStub();
        ReflectionUtil::setId($entity, 300);
        $entity->addAppliedPromotion($targetAppliedPromotion);

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($targetAppliedPromotion));

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertCount(1, $entity->getAppliedPromotions());
        self::assertSame($targetAppliedPromotion, $entity->getAppliedPromotions()->first());
        self::assertTrue($targetAppliedPromotion->isActive());
        self::assertEquals('Updated Spring Sale', $targetAppliedPromotion->getPromotionName());
    }

    public function testSynchronizeFromDraftRemovesAppliedPromotionNotInSource(): void
    {
        $draft = new OrderStub();
        ReflectionUtil::setId($draft, 200);

        $targetAppliedPromotion = new AppliedPromotion();
        $targetAppliedPromotion->setSourcePromotionId(100);

        $entity = new OrderStub();
        ReflectionUtil::setId($entity, 300);
        $entity->addAppliedPromotion($targetAppliedPromotion);

        $this->entityManager->expects(self::once())
            ->method('remove')
            ->with(self::identicalTo($targetAppliedPromotion));

        self::assertCount(1, $entity->getAppliedPromotions());

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertCount(0, $entity->getAppliedPromotions());
    }

    public function testSynchronizeToDraftCopiesPromotions(): void
    {
        $sourceAppliedPromotion = new AppliedPromotion();
        $sourceAppliedPromotion->setActive(true);
        $sourceAppliedPromotion->setSourcePromotionId(100);
        $sourceAppliedPromotion->setPromotionName('Order Promotion');
        $sourceAppliedPromotion->setType('order');

        $entity = new OrderStub();
        ReflectionUtil::setId($entity, 200);
        $entity->addAppliedPromotion($sourceAppliedPromotion);

        $draft = new OrderStub();
        ReflectionUtil::setId($draft, 300);

        $this->synchronizer->synchronizeToDraft($entity, $draft);

        self::assertCount(1, $draft->getAppliedPromotions());
    }

    public function testSynchronizeFromDraftHandlesAppliedCoupon(): void
    {
        $sourceCoupon = new AppliedCoupon();
        $sourceCoupon->setCouponCode('SPRING2026');
        $sourceCoupon->setSourcePromotionId(100);
        $sourceCoupon->setSourceCouponId(200);

        $sourceAppliedPromotion = new AppliedPromotion();
        $sourceAppliedPromotion->setSourcePromotionId(100);
        $sourceAppliedPromotion->setType('line_item');
        $sourceAppliedPromotion->setPromotionName('Coupon Promotion');
        $sourceAppliedPromotion->setAppliedCoupon($sourceCoupon);

        $draft = new OrderStub();
        ReflectionUtil::setId($draft, 300);
        $draft->addAppliedPromotion($sourceAppliedPromotion);

        $entity = new OrderStub();
        ReflectionUtil::setId($entity, 400);

        $persistedEntities = [];
        $this->entityManager->expects(self::exactly(2))
            ->method('persist')
            ->willReturnCallback(function ($e) use (&$persistedEntities) {
                $persistedEntities[] = $e;
            });

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertCount(1, $entity->getAppliedPromotions());

        $targetPromotion = $entity->getAppliedPromotions()->first();
        self::assertNotNull($targetPromotion->getAppliedCoupon());

        $targetCoupon = $targetPromotion->getAppliedCoupon();
        self::assertNotSame($sourceCoupon, $targetCoupon);
        self::assertEquals('SPRING2026', $targetCoupon->getCouponCode());
        self::assertEquals(100, $targetCoupon->getSourcePromotionId());
        self::assertEquals(200, $targetCoupon->getSourceCouponId());
    }

    public function testSyncAppliedDiscountsReplacesAll(): void
    {
        $sourceDiscount = new AppliedDiscount();
        $sourceDiscount->setAmount(10.50);
        $sourceDiscount->setCurrency('USD');

        $sourceAppliedPromotion = new AppliedPromotion();
        $sourceAppliedPromotion->setSourcePromotionId(100);
        $sourceAppliedPromotion->setType('order');
        $sourceAppliedPromotion->setPromotionName('Discount Promotion');
        $sourceAppliedPromotion->addAppliedDiscount($sourceDiscount);

        $draft = new OrderStub();
        ReflectionUtil::setId($draft, 300);
        $draft->addAppliedPromotion($sourceAppliedPromotion);

        $targetDiscount = new AppliedDiscount();
        $targetDiscount->setAmount(5.00);
        $targetDiscount->setCurrency('GBP');

        $targetAppliedPromotion = new AppliedPromotion();
        $targetAppliedPromotion->setSourcePromotionId(100);
        $targetAppliedPromotion->setType('order');
        $targetAppliedPromotion->addAppliedDiscount($targetDiscount);

        $entity = new OrderStub();
        ReflectionUtil::setId($entity, 400);
        $entity->addAppliedPromotion($targetAppliedPromotion);

        $removedEntities = [];
        $persistedEntities = [];

        $this->entityManager->expects(self::once())
            ->method('remove')
            ->willReturnCallback(function ($e) use (&$removedEntities) {
                $removedEntities[] = $e;
            });

        $this->entityManager->expects(self::exactly(2))
            ->method('persist')
            ->willReturnCallback(function ($e) use (&$persistedEntities) {
                $persistedEntities[] = $e;
            });

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertSame($targetDiscount, $removedEntities[0]);
        self::assertCount(1, $targetAppliedPromotion->getAppliedDiscounts());
    }

    public function testSynchronizeFromDraftRemovesExistingCouponWhenSourceHasNone(): void
    {
        $targetCoupon = new AppliedCoupon();
        $targetCoupon->setCouponCode('OLD-CODE');
        $targetCoupon->setSourcePromotionId(100);
        $targetCoupon->setSourceCouponId(50);

        $sourceAppliedPromotion = new AppliedPromotion();
        $sourceAppliedPromotion->setSourcePromotionId(100);
        $sourceAppliedPromotion->setType('order');
        $sourceAppliedPromotion->setPromotionName('Promotion Without Coupon');
        // No appliedCoupon set on source

        $targetAppliedPromotion = new AppliedPromotion();
        $targetAppliedPromotion->setSourcePromotionId(100);
        $targetAppliedPromotion->setPromotionName('Promotion With Old Coupon');
        $targetAppliedPromotion->setAppliedCoupon($targetCoupon);

        $draft = new OrderStub();
        ReflectionUtil::setId($draft, 300);
        $draft->addAppliedPromotion($sourceAppliedPromotion);

        $entity = new OrderStub();
        ReflectionUtil::setId($entity, 400);
        $entity->addAppliedPromotion($targetAppliedPromotion);
        $entity->addAppliedCoupon($targetCoupon);

        $this->entityManager->expects(self::once())
            ->method('remove')
            ->with(self::identicalTo($targetCoupon));

        $this->entityManager->expects(self::any())
            ->method('persist');

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertNull($targetAppliedPromotion->getAppliedCoupon());
        self::assertFalse($entity->getAppliedCoupons()->contains($targetCoupon));
    }

    public function testSynchronizeFromDraftUpdatesExistingCouponInPlace(): void
    {
        $sourceCoupon = new AppliedCoupon();
        $sourceCoupon->setCouponCode('NEW-CODE');
        $sourceCoupon->setSourcePromotionId(100);
        $sourceCoupon->setSourceCouponId(300);

        $sourceAppliedPromotion = new AppliedPromotion();
        $sourceAppliedPromotion->setSourcePromotionId(100);
        $sourceAppliedPromotion->setType('order');
        $sourceAppliedPromotion->setPromotionName('Updated Promotion');
        $sourceAppliedPromotion->setAppliedCoupon($sourceCoupon);

        $existingCoupon = new AppliedCoupon();
        $existingCoupon->setCouponCode('OLD-CODE');
        $existingCoupon->setSourcePromotionId(100);
        $existingCoupon->setSourceCouponId(200);

        $targetAppliedPromotion = new AppliedPromotion();
        $targetAppliedPromotion->setSourcePromotionId(100);
        $targetAppliedPromotion->setPromotionName('Old Promotion');
        $targetAppliedPromotion->setAppliedCoupon($existingCoupon);

        $draft = new OrderStub();
        ReflectionUtil::setId($draft, 300);
        $draft->addAppliedPromotion($sourceAppliedPromotion);

        $entity = new OrderStub();
        ReflectionUtil::setId($entity, 400);
        $entity->addAppliedPromotion($targetAppliedPromotion);
        $entity->addAppliedCoupon($existingCoupon);

        $this->entityManager->expects(self::any())
            ->method('persist');

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        $resultCoupon = $targetAppliedPromotion->getAppliedCoupon();
        self::assertSame($existingCoupon, $resultCoupon);
        self::assertEquals('NEW-CODE', $resultCoupon->getCouponCode());
        self::assertEquals(100, $resultCoupon->getSourcePromotionId());
        self::assertEquals(300, $resultCoupon->getSourceCouponId());
    }

    public function testSynchronizeFromDraftDiscountLineItemUsesDraftSourceWhenNotEmpty(): void
    {
        $originalLineItem = new OrderLineItem();
        ReflectionUtil::setId($originalLineItem, 50);

        $draftLineItem = new OrderLineItem();
        ReflectionUtil::setId($draftLineItem, 200);
        $draftLineItem->setDraftSource($originalLineItem);
        $draftLineItem->setDraftSessionUuid('session-xyz');

        $sourceDiscount = new AppliedDiscount();
        $sourceDiscount->setAmount(30.00);
        $sourceDiscount->setCurrency('USD');
        $sourceDiscount->setLineItem($draftLineItem);

        $sourceAppliedPromotion = new AppliedPromotion();
        $sourceAppliedPromotion->setSourcePromotionId(100);
        $sourceAppliedPromotion->setType('line_item');
        $sourceAppliedPromotion->setPromotionName('Line Item Discount');
        $sourceAppliedPromotion->addAppliedDiscount($sourceDiscount);

        $draft = new OrderStub();
        ReflectionUtil::setId($draft, 300);
        $draft->addAppliedPromotion($sourceAppliedPromotion);

        $entity = new OrderStub();
        ReflectionUtil::setId($entity, 400);

        $this->orderLineItemDraftSynchronizer->expects(self::never())
            ->method('synchronizeFromDraft');

        $this->entityManager->expects(self::any())
            ->method('persist');

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        $clonedDiscount = $entity->getAppliedPromotions()->first()->getAppliedDiscounts()->first();
        self::assertSame($originalLineItem, $clonedDiscount->getLineItem());
    }

    public function testSynchronizeFromDraftDiscountCreatesNewLineItemWhenDraftSourceIsNull(): void
    {
        $draftLineItem = new OrderLineItem();
        ReflectionUtil::setId($draftLineItem, 200);
        $draftLineItem->setDraftSource(null);
        $draftLineItem->setDraftSessionUuid('session-xyz');

        $sourceDiscount = new AppliedDiscount();
        $sourceDiscount->setAmount(20.00);
        $sourceDiscount->setCurrency('EUR');
        $sourceDiscount->setLineItem($draftLineItem);

        $sourceAppliedPromotion = new AppliedPromotion();
        $sourceAppliedPromotion->setSourcePromotionId(100);
        $sourceAppliedPromotion->setType('line_item');
        $sourceAppliedPromotion->setPromotionName('Self-Referencing Discount');
        $sourceAppliedPromotion->addAppliedDiscount($sourceDiscount);

        $draft = new OrderStub();
        ReflectionUtil::setId($draft, 300);
        $draft->addAppliedPromotion($sourceAppliedPromotion);

        $entity = new OrderStub();
        ReflectionUtil::setId($entity, 400);

        $this->orderLineItemDraftSynchronizer->expects(self::once())
            ->method('synchronizeFromDraft')
            ->with($draftLineItem, self::isInstanceOf(OrderLineItem::class));

        $this->entityManager->expects(self::any())
            ->method('persist');

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        $clonedDiscount = $entity->getAppliedPromotions()->first()->getAppliedDiscounts()->first();
        self::assertNotNull($clonedDiscount->getLineItem());
        self::assertNotSame($draftLineItem, $clonedDiscount->getLineItem());
        self::assertCount(1, $entity->getLineItems());
    }

    public function testSynchronizeFromDraftDiscountFindsMatchingTargetLineItemByDraftSourceId(): void
    {
        $sourceLineItem = new OrderLineItem();
        ReflectionUtil::setId($sourceLineItem, 100);
        // No draftSource – sourceLineItem is the original

        $draftSourceOfTarget = new OrderLineItem();
        ReflectionUtil::setId($draftSourceOfTarget, 100);

        $targetLineItem = new OrderLineItem();
        $targetLineItem->setDraftSource($draftSourceOfTarget);

        $sourceDiscount = new AppliedDiscount();
        $sourceDiscount->setAmount(45.00);
        $sourceDiscount->setCurrency('JPY');
        $sourceDiscount->setLineItem($sourceLineItem);

        $sourceAppliedPromotion = new AppliedPromotion();
        $sourceAppliedPromotion->setSourcePromotionId(100);
        $sourceAppliedPromotion->setType('line_item');
        $sourceAppliedPromotion->setPromotionName('Target Line Item Discount');
        $sourceAppliedPromotion->addAppliedDiscount($sourceDiscount);

        $draft = new OrderStub();
        ReflectionUtil::setId($draft, 300);
        $draft->addAppliedPromotion($sourceAppliedPromotion);

        $entity = new OrderStub();
        ReflectionUtil::setId($entity, 400);
        $entity->addLineItem($targetLineItem);

        $this->orderLineItemDraftFactory->expects(self::never())
            ->method('createDraft');

        $this->entityManager->expects(self::any())
            ->method('persist');

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        $clonedDiscount = $entity->getAppliedPromotions()->first()->getAppliedDiscounts()->first();
        self::assertSame($targetLineItem, $clonedDiscount->getLineItem());
    }

    public function testSynchronizeFromDraftDiscountCreatesNewLineItemDraftWhenNoMatchFound(): void
    {
        $sourceLineItem = new OrderLineItem();
        ReflectionUtil::setId($sourceLineItem, 100);
        // No draftSource, no matching line items in target order

        $sourceDiscount = new AppliedDiscount();
        $sourceDiscount->setAmount(55.00);
        $sourceDiscount->setCurrency('CAD');
        $sourceDiscount->setLineItem($sourceLineItem);

        $sourceAppliedPromotion = new AppliedPromotion();
        $sourceAppliedPromotion->setSourcePromotionId(100);
        $sourceAppliedPromotion->setType('line_item');
        $sourceAppliedPromotion->setPromotionName('Create New Line Item Discount');
        $sourceAppliedPromotion->addAppliedDiscount($sourceDiscount);

        $draft = new OrderStub();
        ReflectionUtil::setId($draft, 300);
        $draft->addAppliedPromotion($sourceAppliedPromotion);

        $entity = new OrderStub();
        ReflectionUtil::setId($entity, 400);
        $entity->setDraftSessionUuid('session-xyz');

        $createdLineItem = new OrderLineItem();
        ReflectionUtil::setId($createdLineItem, 500);

        $this->orderLineItemDraftFactory->expects(self::once())
            ->method('createDraft')
            ->with($sourceLineItem, 'session-xyz')
            ->willReturn($createdLineItem);

        $this->entityManager->expects(self::any())
            ->method('persist');

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        $clonedDiscount = $entity->getAppliedPromotions()->first()->getAppliedDiscounts()->first();
        self::assertSame($createdLineItem, $clonedDiscount->getLineItem());
        self::assertTrue($entity->getLineItems()->contains($createdLineItem));
    }
}
