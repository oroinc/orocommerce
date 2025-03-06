<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountDecorator;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;
use Oro\Bundle\PromotionBundle\Provider\OrderPromotionDiscountsProviderDecorator;
use Oro\Bundle\PromotionBundle\Provider\PromotionDiscountsProviderInterface;
use Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Stub\DiscountStub;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Checkout;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderPromotionDiscountsProviderDecoratorTest extends TestCase
{
    private PromotionDiscountsProviderInterface|MockObject $baseDiscountsProvider;

    private PromotionAwareEntityHelper|MockObject $promotionAwareHelper;

    private OrderPromotionDiscountsProviderDecorator $discountsProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->baseDiscountsProvider = $this->createMock(PromotionDiscountsProviderInterface::class);
        $this->promotionAwareHelper = $this->createMock(PromotionAwareEntityHelper::class);

        $this->discountsProvider = new OrderPromotionDiscountsProviderDecorator(
            $this->baseDiscountsProvider,
            $this->promotionAwareHelper
        );
    }

    private function getOrder(PersistentCollection $lineItems, PersistentCollection $appliedPromotions): Order
    {
        $order = new Order();
        ReflectionUtil::setId($order, 1);
        $order->setLineItems($lineItems);
        $order->setAppliedPromotions($appliedPromotions);

        return $order;
    }

    private function getOrderLineItem(Product $product, ?string $productSku = null): OrderLineItem
    {
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductSku($productSku ?? $product->getSku());

        return $lineItem;
    }

    private function getProduct(string $sku): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, 1);
        $product->setSku($sku);

        return $product;
    }

    private function getPromotion(int $id, bool $useCoupons = false): Promotion
    {
        $promotion = new Promotion();
        ReflectionUtil::setId($promotion, $id);
        $promotion->setUseCoupons($useCoupons);

        return $promotion;
    }

    private function getAppliedPromotion(int $sourcePromotionId): AppliedPromotion
    {
        $appliedPromotion = new AppliedPromotion();
        $appliedPromotion->setSourcePromotionId($sourcePromotionId);

        return $appliedPromotion;
    }

    private function getDiscount(Promotion $promotion): DiscountStub
    {
        $discount = new DiscountStub();
        $discount->setPromotion($promotion);

        return $discount;
    }

    private function getPersistentCollection(array $collection): PersistentCollection
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($this->createMock(UnitOfWork::class));

        return new PersistentCollection(
            $em,
            $this->createMock(ClassMetadata::class),
            new ArrayCollection($collection)
        );
    }

    public function testGetDiscountsShouldNotDoFiltrationWhenExistingProductWasChanged(): void
    {
        $sourceEntity = $this->getOrder(
            $this->getPersistentCollection([$this->getOrderLineItem($this->getProduct('product_1'), 'custom_sku')]),
            $this->getPersistentCollection([$this->getAppliedPromotion(2)])
        );
        $context = $this->createMock(DiscountContextInterface::class);
        $discounts = [$this->getDiscount($this->getPromotion(1))];

        $this->baseDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with(self::identicalTo($sourceEntity), self::identicalTo($context))
            ->willReturn($discounts);

        $this->promotionAwareHelper->expects(self::once())
            ->method('isPromotionAware')
            ->willReturn(true);

        self::assertSame($discounts, $this->discountsProvider->getDiscounts($sourceEntity, $context));
    }

    public function testThatDiscountsShouldFilteredWhenAppliedPromotionIsRemoved(): void
    {
        $removedApliedPromotion = $this->getAppliedPromotion(1)->setRemoved(true);
        $lineItems = $this->getPersistentCollection([]);
        $lineItems->add($this->getOrderLineItem($this->getProduct('product_1')));
        $sourceEntity = $this->getOrder(
            $lineItems,
            $this->getPersistentCollection([$removedApliedPromotion])
        );
        $context = $this->createMock(DiscountContextInterface::class);
        $discount = $this->getDiscount($this->getPromotion(1));

        $this->baseDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with(self::identicalTo($sourceEntity), self::identicalTo($context))
            ->willReturn([$discount]);

        $this->promotionAwareHelper->expects(self::once())
            ->method('isPromotionAware')
            ->willReturn(true);

        self::assertEquals(
            [new DisabledDiscountDecorator($discount)],
            $this->discountsProvider->getDiscounts($sourceEntity, $context)
        );
    }

    public function testGetDiscountsForAppliedDiscounts(): void
    {
        $sourceEntity = $this->getOrder(
            $this->getPersistentCollection([$this->getOrderLineItem($this->getProduct('product_1'))]),
            $this->getPersistentCollection([$this->getAppliedPromotion(1)])
        );
        $context = $this->createMock(DiscountContextInterface::class);
        $discounts = [$this->getDiscount($this->getPromotion(1))];

        $this->baseDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with(self::identicalTo($sourceEntity), self::identicalTo($context))
            ->willReturn($discounts);

        $this->promotionAwareHelper->expects(self::once())
            ->method('isPromotionAware')
            ->willReturn(true);

        self::assertSame($discounts, $this->discountsProvider->getDiscounts($sourceEntity, $context));
    }

    public function testGetDiscountsForNotAppliedDiscountThatUsesCoupons(): void
    {
        $sourceEntity = $this->getOrder(
            $this->getPersistentCollection([$this->getOrderLineItem($this->getProduct('product_1'))]),
            $this->getPersistentCollection([$this->getAppliedPromotion(2)])
        );
        $context = $this->createMock(DiscountContextInterface::class);
        $discounts = [$this->getDiscount($this->getPromotion(1, true))];

        $this->baseDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with(self::identicalTo($sourceEntity), self::identicalTo($context))
            ->willReturn($discounts);

        $this->promotionAwareHelper->expects(self::once())
            ->method('isPromotionAware')
            ->willReturn(true);

        self::assertSame($discounts, $this->discountsProvider->getDiscounts($sourceEntity, $context));
    }

    public function testGetDiscountsForNotAppliedDiscountThatUsesCouponsWhenNoAppliedDiscounts(): void
    {
        $sourceEntity = $this->getOrder(
            $this->getPersistentCollection([$this->getOrderLineItem($this->getProduct('product_1'))]),
            $this->getPersistentCollection([])
        );
        $context = $this->createMock(DiscountContextInterface::class);
        $discounts = [$this->getDiscount($this->getPromotion(1, true))];

        $this->baseDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with(self::identicalTo($sourceEntity), self::identicalTo($context))
            ->willReturn($discounts);

        $this->promotionAwareHelper->expects(self::once())
            ->method('isPromotionAware')
            ->willReturn(true);

        self::assertSame($discounts, $this->discountsProvider->getDiscounts($sourceEntity, $context));
    }

    public function testGetDiscountsWhenNoAppliedDiscounts(): void
    {
        $sourceEntity = $this->getOrder(
            $this->getPersistentCollection([$this->getOrderLineItem($this->getProduct('product_1'))]),
            $this->getPersistentCollection([])
        );
        $discounts = [$this->getDiscount($this->getPromotion(1))];
        $context = $this->createMock(DiscountContextInterface::class);

        $this->baseDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with(self::identicalTo($sourceEntity), self::identicalTo($context))
            ->willReturn($discounts);

        $this->promotionAwareHelper->expects(self::once())
            ->method('isPromotionAware')
            ->willReturn(true);

        self::assertSame($discounts, $this->discountsProvider->getDiscounts($sourceEntity, $context));
    }

    public function testGetDiscountsForNotOrder(): void
    {
        $sourceEntity = new Checkout();
        $context = $this->createMock(DiscountContextInterface::class);
        $discounts = [$this->getDiscount($this->getPromotion(1))];

        $this->baseDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with(self::identicalTo($sourceEntity), self::identicalTo($context))
            ->willReturn($discounts);

        $this->promotionAwareHelper->expects(self::never())
            ->method('isPromotionAware');

        self::assertSame($discounts, $this->discountsProvider->getDiscounts($sourceEntity, $context));
    }
}
