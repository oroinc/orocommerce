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
use Oro\Bundle\PromotionBundle\Provider\NewPromotionFilterDiscountsProviderDecorator;
use Oro\Bundle\PromotionBundle\Provider\OrderPromotionDiscountsProviderDecorator;
use Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Stub\DiscountStub;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NewPromotionFilterDiscountsProviderDecoratorTest extends TestCase
{
    private OrderPromotionDiscountsProviderDecorator|MockObject $baseDiscountsProvider;

    private PromotionAwareEntityHelper|MockObject $promotionAwareHelper;

    private NewPromotionFilterDiscountsProviderDecorator $discountsProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->baseDiscountsProvider = $this->createMock(OrderPromotionDiscountsProviderDecorator::class);
        $this->promotionAwareHelper = $this->createMock(PromotionAwareEntityHelper::class);

        $this->discountsProvider = new NewPromotionFilterDiscountsProviderDecorator(
            $this->baseDiscountsProvider,
            $this->promotionAwareHelper,
        );
    }

    private function getOrder(PersistentCollection $lineItems, PersistentCollection $appliedPromotions): Order
    {
        $order = new Order();
        ReflectionUtil::setId($order, 1);
        $order->setLineItems($lineItems);
        $order->setAppliedPromotions($appliedPromotions);
        $order->setCreatedAt(new \DateTime('now'));
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

    private function getPromotion(int $id, bool $useCoupons = false, string $time = 'now'): Promotion
    {
        $promotion = new Promotion();
        ReflectionUtil::setId($promotion, $id);
        $promotion->setUseCoupons($useCoupons);
        $promotion->setCreatedAt(new \DateTime($time));
        $promotion->setUpdatedAt(new \DateTime($time));

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
            $this->getPersistentCollection([
                $this->getOrderLineItem($this->getProduct('product_1'), 'custom_sku')
            ]),
            $this->getPersistentCollection([$this->getAppliedPromotion(2)])
        );
        $context = $this->createMock(DiscountContextInterface::class);
        $discounts = [
            $this->getDiscount($this->getPromotion(1, false, "-2 hours")),
        ];

        $this->baseDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with(self::identicalTo($sourceEntity), self::identicalTo($context))
            ->willReturn($discounts);

        $this->promotionAwareHelper->expects(self::once())
            ->method('isPromotionAware')
            ->willReturn(true);

        self::assertSame($discounts, $this->discountsProvider->getDiscounts($sourceEntity, $context));
    }

    public function testGetNewCreatedPromotionForExistingOrder(): void
    {
        $availableDiscounts = [
            $this->getDiscount($this->getPromotion(1)),
            $this->getDiscount($this->getPromotion(2, false, '+2 hours'))
        ];

        $appliedDiscounts = [$this->getAppliedPromotion(1)];

        $sourceEntity = $this->getOrder(
            $this->getPersistentCollection([$this->getOrderLineItem($this->getProduct('product_1'))]),
            $this->getPersistentCollection($appliedDiscounts)
        );

        $context = $this->createMock(DiscountContextInterface::class);

        $this->baseDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with(self::identicalTo($sourceEntity), self::identicalTo($context))
            ->willReturn($availableDiscounts);

        $this->promotionAwareHelper->expects(self::once())
            ->method('isPromotionAware')
            ->willReturn(true);

        $result = $this->discountsProvider->getDiscounts($sourceEntity, $context);
        self::assertInstanceOf(DisabledDiscountDecorator::class, $result[1]);
    }

    public function testGetDiscountsForNotAppliedDiscountThatUsesCoupons(): void
    {
        $sourceEntity = $this->getOrder(
            $this->getPersistentCollection([$this->getOrderLineItem($this->getProduct('product_1'))]),
            $this->getPersistentCollection([$this->getAppliedPromotion(2)])
        );
        $context = $this->createMock(DiscountContextInterface::class);
        $discounts = [
            $this->getDiscount($this->getPromotion(1, true)),
            $this->getDiscount($this->getPromotion(2, true))
        ];

        $this->baseDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with(self::identicalTo($sourceEntity), self::identicalTo($context))
            ->willReturn($discounts);

        $this->promotionAwareHelper->expects(self::once())
            ->method('isPromotionAware')
            ->willReturn(true);

        self::assertSame($discounts, $this->discountsProvider->getDiscounts($sourceEntity, $context));
    }
}
