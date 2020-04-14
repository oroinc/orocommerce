<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountFactory;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Provider\MatchingProductsProvider;
use Oro\Bundle\PromotionBundle\Provider\PromotionDiscountsProvider;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;
use Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Stub\DiscountStub;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\EntityTrait;

class PromotionDiscountsProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var PromotionProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $promotionProvider;

    /**
     * @var DiscountFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $discountFactory;

    /**
     * @var MatchingProductsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $matchingProductsProvider;

    /**
     * @var PromotionDiscountsProvider
     */
    private $promotionDiscountsProvider;

    protected function setUp(): void
    {
        $this->promotionProvider = $this->createMock(PromotionProvider::class);
        $this->discountFactory = $this->createMock(DiscountFactory::class);
        $this->matchingProductsProvider = $this->createMock(MatchingProductsProvider::class);

        $this->promotionDiscountsProvider = new PromotionDiscountsProvider(
            $this->promotionProvider,
            $this->discountFactory,
            $this->matchingProductsProvider
        );
    }

    public function testGetDiscounts()
    {
        $lineItems = [new DiscountLineItem(), new DiscountLineItem()];
        $context = (new DiscountContext())->setLineItems($lineItems);
        $sourceEntity = new Order();

        /** @var Segment $firstSegment */
        $firstSegment = $this->getEntity(Segment::class, ['id' => 1]);
        $firstPromotion = (new Promotion())
            ->setDiscountConfiguration(new DiscountConfiguration())
            ->setProductsSegment($firstSegment);

        /** @var Segment $secondSegment */
        $secondSegment = $this->getEntity(Segment::class, ['id' => 2]);
        $secondPromotion = (new Promotion())
            ->setDiscountConfiguration(new DiscountConfiguration())
            ->setProductsSegment($secondSegment);

        $promotions = [$firstPromotion, $secondPromotion];

        $this->promotionProvider
            ->expects($this->once())
            ->method('getPromotions')
            ->with($sourceEntity)
            ->willReturn($promotions);

        $discounts = [new DiscountStub(), new DiscountStub()];

        $this->discountFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [$firstPromotion->getDiscountConfiguration(), $firstPromotion],
                [$secondPromotion->getDiscountConfiguration(), $secondPromotion]
            )
            ->willReturnOnConsecutiveCalls(...$discounts);

        $firstMatchingProducts = [new Product()];
        $secondMatchingProducts = [new Product(), new Product()];
        $this->matchingProductsProvider
            ->expects($this->exactly(2))
            ->method('getMatchingProducts')
            ->withConsecutive([$firstSegment, $lineItems], [$secondSegment, $lineItems])
            ->willReturnOnConsecutiveCalls($firstMatchingProducts, $secondMatchingProducts);

        $expectedDiscounts = [
            (new DiscountStub())->setMatchingProducts($firstMatchingProducts),
            (new DiscountStub())->setMatchingProducts($secondMatchingProducts)
        ];

        $this->assertEquals(
            $expectedDiscounts,
            $this->promotionDiscountsProvider->getDiscounts($sourceEntity, $context)
        );
    }
}
