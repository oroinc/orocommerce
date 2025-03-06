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
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Provider\OrderPromotionDiscountsProviderDecorator;
use Oro\Bundle\PromotionBundle\Provider\PromotionDiscountsProviderInterface;
use Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Stub\DiscountStub;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTrait;

class OrderPromotionDiscountsProviderDecoratorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var OrderPromotionDiscountsProviderDecorator */
    private $orderPromotionDiscountsProviderDecorator;

    /** @var PromotionDiscountsProviderInterface */
    private $discountsProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->discountsProvider = $this->createMock(PromotionDiscountsProviderInterface::class);
        $this->orderPromotionDiscountsProviderDecorator = new OrderPromotionDiscountsProviderDecorator(
            $this->discountsProvider
        );
    }

    /**
     * Check the case in which the product(item) was changed.
     */
    public function testGetDiscountsWithItemChanged(): void
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1, 'sku' => 'product_1']);
        $lineItem = $this->getEntity(OrderLineItem::class, ['product' => $product, 'productSku' => 'custom_sku']);
        $lineItems = $this->getPersistentCollection([$lineItem]);
        $appliedPromotion = $this->getEntity(AppliedPromotion::class, ['sourcePromotionId' => 2]);
        $appliedPromotions = $this->getPersistentCollection([$appliedPromotion]);

        $order = $this->getOrder($lineItems, $appliedPromotions);
        $promotion = $this->getEntity(Promotion::class, ['id' => 1]);
        $discount = $this->getEntity(DiscountStub::class, ['promotion' => $promotion]);
        $this->assertDiscountProvider([$discount]);

        $discounts = $this->orderPromotionDiscountsProviderDecorator->getDiscounts($order, new DiscountContext());
        $this->assertEquals([$discount], $discounts);
    }

    /**
     * Check the case in which the product(item) was added to the item list.
     */
    public function testGetDiscountsWithItemsChanged(): void
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1, 'sku' => 'product_1']);
        $lineItem = $this->getEntity(OrderLineItem::class, ['product' => $product, 'productSku' => $product->getSku()]);
        $lineItems = $this->getPersistentCollection();
        $lineItems->add($lineItem);

        $appliedPromotion = $this->getEntity(AppliedPromotion::class, ['sourcePromotionId' => 2]);
        $appliedPromotions = $this->getPersistentCollection([$appliedPromotion]);

        $order = $this->getOrder($lineItems, $appliedPromotions);
        $promotion = $this->getEntity(Promotion::class, ['id' => 1]);
        $discount = $this->getEntity(DiscountStub::class, ['promotion' => $promotion]);
        $this->assertDiscountProvider([$discount]);

        $discounts = $this->orderPromotionDiscountsProviderDecorator->getDiscounts($order, new DiscountContext());
        $this->assertEquals([$discount], $discounts);
    }

    public function testThatDiscountsShouldFilteredWhenAppliedPromotionIsRemoved(): void
    {
        $removedAppliedPromotion = $this->getAppliedPromotion(1)->setRemoved(true);
        $lineItems = $this->getPersistentCollection([]);
        $lineItems->add($this->getOrderLineItem($this->getProduct(1, 'product_1')));
        $sourceEntity = $this->getOrder(
            $lineItems,
            $this->getPersistentCollection([$removedAppliedPromotion])
        );
        $context = $this->createMock(DiscountContextInterface::class);
        $discount = $this->getDiscount($this->getPromotion(1));

        $this->discountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with(self::identicalTo($sourceEntity), self::identicalTo($context))
            ->willReturn([$discount]);

        self::assertEquals(
            [new DisabledDiscountDecorator($discount)],
            $this->orderPromotionDiscountsProviderDecorator->getDiscounts($sourceEntity, $context)
        );
    }

    /**
     * @param array $discounts
     */
    private function assertDiscountProvider($discounts = []): void
    {
        $this->discountsProvider
            ->expects($this->once())
            ->method('getDiscounts')
            ->willReturn($discounts);
    }

    private function getOrder(PersistentCollection $lineItems, PersistentCollection $appliedPromotions): Order
    {
        /** @var Order $order */
        $order = $this->getEntity(Order::class, ['id' => 1]);
        $order->setLineItems($lineItems);
        $order->setAppliedPromotions($appliedPromotions);

        return $order;
    }

    /**
     * @param array $collection
     *
     * @return PersistentCollection
     */
    private function getPersistentCollection(array $collection = [])
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getEntityManager();
        /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject $metadata */
        $metadata = $this->createMock(ClassMetadata::class);

        return new PersistentCollection($em, $metadata, new ArrayCollection($collection));
    }

    /**
     * @return EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getEntityManager()
    {
        $uow = $this->createMock(UnitOfWork::class);
        $uow
            ->expects($this->any())
            ->method('cancelOrphanRemoval');
        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        return $em;
    }

    private function getProduct(int $id, string $sku): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);
        $product->setSku($sku);

        return $product;
    }

    private function getOrderLineItem(Product $product, ?string $productSku = null): OrderLineItem
    {
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductSku($productSku ?? $product->getSku());

        return $lineItem;
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
}
