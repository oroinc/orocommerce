<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountFactory;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Model\MultiShippingPromotionData;
use Oro\Bundle\PromotionBundle\Provider\MatchingProductsProvider;
use Oro\Bundle\PromotionBundle\Provider\MatchingProductsProviderInterface;
use Oro\Bundle\PromotionBundle\Provider\PromotionDiscountsProvider;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;
use Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Stub\DiscountStub;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\ReflectionUtil;

class PromotionDiscountsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PromotionProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionProvider;

    /** @var DiscountFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $discountFactory;

    /** @var MatchingProductsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $matchingProductsProvider;

    /** @var PromotionDiscountsProvider */
    private $discountsProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->promotionProvider = $this->createMock(PromotionProvider::class);
        $this->discountFactory = $this->createMock(DiscountFactory::class);
        $this->matchingProductsProvider = $this->createMock(MatchingProductsProviderInterface::class);

        $this->discountsProvider = new PromotionDiscountsProvider(
            $this->promotionProvider,
            $this->discountFactory,
            $this->matchingProductsProvider
        );
    }

    private function getSegment(int $id): Segment
    {
        $segment = new Segment();
        ReflectionUtil::setId($segment, $id);

        return $segment;
    }

    private function getPromotion(Segment $segment): Promotion
    {
        $promotion = new Promotion();
        $promotion->setDiscountConfiguration(new DiscountConfiguration());
        $promotion->setProductsSegment($segment);

        return $promotion;
    }

    private function getMultiShippingPromotion(Segment $segment, array $lineItems): MultiShippingPromotionData
    {
        return new MultiShippingPromotionData($this->getPromotion($segment), $lineItems);
    }

    public function testGetDiscounts(): void
    {
        $organization = new Organization();
        $sourceEntity = new Order();
        $lineItems = [new DiscountLineItem(), new DiscountLineItem()];

        $context = new DiscountContext();
        $context->setLineItems($lineItems);

        $firstSegment = $this->getSegment(1);
        $firstPromotion = $this->getPromotion($firstSegment);
        $firstPromotion->setOrganization($organization);

        $secondSegment = $this->getSegment(2);
        $secondPromotion = $this->getMultiShippingPromotion($secondSegment, [$lineItems[1]]);

        $firstDiscount = new DiscountStub();
        $secondDiscount = new DiscountStub();

        $this->promotionProvider->expects(self::once())
            ->method('getPromotions')
            ->with($sourceEntity)
            ->willReturn([$firstPromotion, $secondPromotion]);

        $this->discountFactory->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive(
                [$firstPromotion->getDiscountConfiguration(), $firstPromotion],
                [$secondPromotion->getDiscountConfiguration(), $secondPromotion]
            )
            ->willReturnOnConsecutiveCalls(
                $firstDiscount,
                $secondDiscount
            );

        $firstMatchingProducts = [new Product()];
        $secondMatchingProducts = [new Product()];
        $this->matchingProductsProvider->expects(self::exactly(2))
            ->method('getMatchingProducts')
            ->withConsecutive(
                [$firstSegment, $lineItems, $organization],
                [$secondSegment, $secondPromotion->getLineItems(), null]
            )
            ->willReturnOnConsecutiveCalls(
                $firstMatchingProducts,
                $secondMatchingProducts
            );

        $result = $this->discountsProvider->getDiscounts($sourceEntity, $context);
        self::assertCount(2, $result);
        self::assertSame($firstDiscount, $result[0]);
        self::assertSame($firstMatchingProducts, $result[0]->getMatchingProducts());
        self::assertSame($secondDiscount, $result[1]);
        self::assertSame($secondMatchingProducts, $result[1]->getMatchingProducts());
    }
}
