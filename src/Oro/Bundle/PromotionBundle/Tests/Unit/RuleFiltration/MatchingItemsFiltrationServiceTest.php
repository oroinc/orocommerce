<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountProductUnitCodeAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Provider\MatchingProductsProvider;
use Oro\Bundle\PromotionBundle\RuleFiltration\MatchingItemsFiltrationService;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\EntityTrait;

class MatchingItemsFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const UNIT_CODE_ITEM = 'item';
    private const UNIT_CODE_SET = 'set';

    /** @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $filtrationService;

    /** @var MatchingProductsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $matchingProductsProvider;

    /** @var MatchingItemsFiltrationService */
    private $matchingItemsFiltrationService;

    protected function setUp(): void
    {
        $this->filtrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->matchingProductsProvider = $this->createMock(MatchingProductsProvider::class);

        $this->matchingItemsFiltrationService = new MatchingItemsFiltrationService(
            $this->filtrationService,
            $this->matchingProductsProvider
        );
    }

    public function testGetFilteredRuleOwnersWhenNoLineItemsSetInContext()
    {
        $this->configureFiltrationService();

        $this->matchingProductsProvider->expects($this->never())
            ->method('getMatchingProducts');

        $promotion = $this->createPromotion(new Segment());
        $this->assertEmpty($this->matchingItemsFiltrationService->getFilteredRuleOwners([$promotion], []));
    }

    public function testGetFilteredRuleOwnersWhenNotPromotionRuleOwnerGiven()
    {
        $ruleOwner = $this->createMock(RuleOwnerInterface::class);

        $product = new Product();
        $lineItems = [(new DiscountLineItem())->setProduct($product)];

        $this->configureFiltrationService();

        $this->matchingProductsProvider->expects($this->never())
            ->method('getMatchingProducts');

        $this->assertEmpty(
            $this->matchingItemsFiltrationService->getFilteredRuleOwners(
                [$ruleOwner],
                [ContextDataConverterInterface::LINE_ITEMS => $lineItems]
            )
        );
    }

    public function testGetFilteredRuleOwnersWhenNoMatchingProductsFound()
    {
        $firstPromotionSegment = new Segment();
        $firstPromotion = $this->createPromotion($firstPromotionSegment);
        $secondPromotionSegment = new Segment();
        $secondPromotion = $this->createPromotion($secondPromotionSegment, self::UNIT_CODE_ITEM);

        $product = new Product();
        $lineItems = [
            (new DiscountLineItem())
                ->setProduct($product)
                ->setProductUnitCode(self::UNIT_CODE_ITEM)
        ];

        $this->configureFiltrationService();

        $this->matchingProductsProvider->expects($this->exactly(2))
            ->method('getMatchingProducts')
            ->withConsecutive([$firstPromotionSegment, $lineItems], [$secondPromotionSegment, $lineItems])
            ->willReturn([]);

        $this->assertEmpty(
            $this->matchingItemsFiltrationService->getFilteredRuleOwners(
                [$firstPromotion, $secondPromotion],
                [ContextDataConverterInterface::LINE_ITEMS => $lineItems]
            )
        );
    }

    public function testGetFilteredRuleOwnersWithMatchingProductsAndNotUnitAwarePromotion()
    {
        $promotionSegment = new Segment();
        $promotion = $this->createPromotion($promotionSegment);

        $product = new Product();
        $lineItems = [
            (new DiscountLineItem())
                ->setProduct($product)
                ->setProductUnitCode(self::UNIT_CODE_ITEM)
        ];

        $this->configureFiltrationService();

        $this->matchingProductsProvider->expects($this->once())
            ->method('getMatchingProducts')
            ->with($promotionSegment, $lineItems)
            ->willReturn([$product]);

        $this->assertEquals(
            [$promotion],
            $this->matchingItemsFiltrationService->getFilteredRuleOwners(
                [$promotion],
                [ContextDataConverterInterface::LINE_ITEMS => $lineItems]
            )
        );
    }

    public function testGetFilteredRuleOwnersWithUnitAwarePromotionWhenUnitsDiffer()
    {
        $promotionSegment = new Segment();
        $promotion = $this->createPromotion($promotionSegment, self::UNIT_CODE_SET);

        $product = new Product();
        $lineItems = [
            (new DiscountLineItem())
                ->setProduct($product)
                ->setProductUnitCode(self::UNIT_CODE_ITEM)
        ];

        $this->configureFiltrationService();

        $this->matchingProductsProvider->expects($this->once())
            ->method('getMatchingProducts')
            ->with($promotionSegment, $lineItems)
            ->willReturn([$product]);

        $this->assertEmpty(
            $this->matchingItemsFiltrationService->getFilteredRuleOwners(
                [$promotion],
                [ContextDataConverterInterface::LINE_ITEMS => $lineItems]
            )
        );
    }

    public function testGetFilteredRuleOwnersWithUnitAwarePromotionWhenUnitsMatch()
    {
        $promotionSegment = new Segment();
        $promotion = $this->createPromotion($promotionSegment, self::UNIT_CODE_ITEM);

        $product = new Product();
        $lineItems = [
            (new DiscountLineItem())
                ->setProduct($product)
                ->setProductUnitCode(self::UNIT_CODE_ITEM)
        ];

        $this->configureFiltrationService();

        $this->matchingProductsProvider->expects($this->once())
            ->method('getMatchingProducts')
            ->with($promotionSegment, $lineItems)
            ->willReturn([$product]);

        $this->assertEquals(
            [$promotion],
            $this->matchingItemsFiltrationService->getFilteredRuleOwners(
                [$promotion],
                [ContextDataConverterInterface::LINE_ITEMS => $lineItems]
            )
        );
    }

    private function createPromotion(Segment $segment, string $productUnitCode = null): PromotionDataInterface
    {
        $options = [];
        if ($productUnitCode) {
            $options[DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE] = $productUnitCode;
        }
        $discountConfiguration = $this->getEntity(DiscountConfiguration::class, ['options' => $options]);

        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects($this->any())
            ->method('getProductsSegment')
            ->willReturn($segment);
        $promotion->expects($this->any())
            ->method('getDiscountConfiguration')
            ->willReturn($discountConfiguration);

        return $promotion;
    }

    private function configureFiltrationService()
    {
        $this->filtrationService->expects($this->any())
            ->method('getFilteredRuleOwners')
            ->willReturnCallback(function ($ruleOwners) {
                return $ruleOwners;
            });
    }

    public function testFilterIsSkippable()
    {
        $this->filtrationService->expects($this->never())
            ->method('getFilteredRuleOwners');

        $ruleOwner = $this->createMock(RuleOwnerInterface::class);
        $this->matchingItemsFiltrationService->getFilteredRuleOwners(
            [$ruleOwner],
            ['skip_filters' => [get_class($this->matchingItemsFiltrationService) => true]]
        );
    }
}
