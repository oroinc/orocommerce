<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountProductUnitCodeAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Provider\MatchingProductsProviderInterface;
use Oro\Bundle\PromotionBundle\RuleFiltration\MatchingItemsFiltrationService;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class MatchingItemsFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    private const UNIT_CODE_ITEM = 'item';
    private const UNIT_CODE_SET = 'set';

    /** @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $baseFiltrationService;

    /** @var MatchingProductsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $matchingProductsProvider;

    /** @var MatchingItemsFiltrationService */
    private $filtrationService;

    #[\Override]
    protected function setUp(): void
    {
        $this->baseFiltrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->matchingProductsProvider = $this->createMock(MatchingProductsProviderInterface::class);

        $this->filtrationService = new MatchingItemsFiltrationService(
            $this->baseFiltrationService,
            $this->matchingProductsProvider
        );
    }

    private function getPromotion(Segment $segment, ?string $productUnitCode = null): PromotionDataInterface
    {
        $options = [];
        if ($productUnitCode) {
            $options[DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE] = $productUnitCode;
        }
        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setOptions($options);

        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects(self::any())
            ->method('getProductsSegment')
            ->willReturn($segment);
        $promotion->expects(self::any())
            ->method('getDiscountConfiguration')
            ->willReturn($discountConfiguration);

        return $promotion;
    }

    private function getDiscountLineItem(Product $product, ?string $productUnitCode = null): DiscountLineItem
    {
        $discountLineItem = new DiscountLineItem();
        $discountLineItem->setProduct($product);
        if (null !== $productUnitCode) {
            $discountLineItem->setProductUnitCode($productUnitCode);
        }

        return $discountLineItem;
    }

    public function testShouldBeSkippable(): void
    {
        $ruleOwners = [$this->createMock(RuleOwnerInterface::class)];

        $this->baseFiltrationService->expects(self::never())
            ->method('getFilteredRuleOwners');

        self::assertSame(
            $ruleOwners,
            $this->filtrationService->getFilteredRuleOwners(
                $ruleOwners,
                ['skip_filters' => [MatchingItemsFiltrationService::class => true]]
            )
        );
    }

    public function testGetFilteredRuleOwnersWhenNoLineItemsSetInContext(): void
    {
        $this->baseFiltrationService->expects(self::never())
            ->method('getFilteredRuleOwners');

        $this->matchingProductsProvider->expects(self::never())
            ->method('getMatchingProducts');

        $promotion = $this->getPromotion(new Segment());
        self::assertSame([], $this->filtrationService->getFilteredRuleOwners([$promotion], []));
    }

    public function testGetFilteredRuleOwnersWhenNotPromotionRuleOwnerGiven(): void
    {
        $ruleOwner = $this->createMock(RuleOwnerInterface::class);

        $product = new Product();
        $lineItems = [$this->getDiscountLineItem($product)];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->willReturnCallback(function ($ruleOwners) {
                return $ruleOwners;
            });

        $this->matchingProductsProvider->expects(self::never())
            ->method('getMatchingProducts');

        self::assertSame(
            [],
            $this->filtrationService->getFilteredRuleOwners(
                [$ruleOwner],
                [ContextDataConverterInterface::LINE_ITEMS => $lineItems]
            )
        );
    }

    public function testGetFilteredRuleOwnersWhenNoMatchingProductsFound(): void
    {
        $firstPromotionSegment = new Segment();
        $firstPromotion = $this->getPromotion($firstPromotionSegment);
        $secondPromotionSegment = new Segment();
        $secondPromotion = $this->getPromotion($secondPromotionSegment, self::UNIT_CODE_ITEM);

        $product = new Product();
        $lineItems = [$this->getDiscountLineItem($product, self::UNIT_CODE_ITEM)];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->willReturnCallback(function ($ruleOwners) {
                return $ruleOwners;
            });

        $this->matchingProductsProvider->expects(self::exactly(2))
            ->method('getMatchingProducts')
            ->withConsecutive(
                [$firstPromotionSegment, $lineItems],
                [$secondPromotionSegment, $lineItems]
            )
            ->willReturn([]);

        self::assertSame(
            [],
            $this->filtrationService->getFilteredRuleOwners(
                [$firstPromotion, $secondPromotion],
                [ContextDataConverterInterface::LINE_ITEMS => $lineItems]
            )
        );
    }

    public function testGetFilteredRuleOwnersWithMatchingProductsAndNotUnitAwarePromotion(): void
    {
        $promotionSegment = new Segment();
        $promotion = $this->getPromotion($promotionSegment);

        $product = new Product();
        $lineItems = [$this->getDiscountLineItem($product, self::UNIT_CODE_ITEM)];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->willReturnCallback(function ($ruleOwners) {
                return $ruleOwners;
            });

        $this->matchingProductsProvider->expects(self::once())
            ->method('getMatchingProducts')
            ->with($promotionSegment, $lineItems)
            ->willReturn([$product]);

        self::assertEquals(
            [$promotion],
            $this->filtrationService->getFilteredRuleOwners(
                [$promotion],
                [ContextDataConverterInterface::LINE_ITEMS => $lineItems]
            )
        );
    }

    public function testGetFilteredRuleOwnersWithUnitAwarePromotionWhenUnitsDiffer(): void
    {
        $promotionSegment = new Segment();
        $promotion = $this->getPromotion($promotionSegment, self::UNIT_CODE_SET);

        $product = new Product();
        $lineItems = [$this->getDiscountLineItem($product, self::UNIT_CODE_ITEM)];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->willReturnCallback(function ($ruleOwners) {
                return $ruleOwners;
            });

        $this->matchingProductsProvider->expects(self::once())
            ->method('getMatchingProducts')
            ->with($promotionSegment, $lineItems)
            ->willReturn([$product]);

        self::assertSame(
            [],
            $this->filtrationService->getFilteredRuleOwners(
                [$promotion],
                [ContextDataConverterInterface::LINE_ITEMS => $lineItems]
            )
        );
    }

    public function testGetFilteredRuleOwnersWithUnitAwarePromotionWhenUnitsMatch(): void
    {
        $promotionSegment = new Segment();
        $promotion = $this->getPromotion($promotionSegment, self::UNIT_CODE_ITEM);

        $product = new Product();
        $lineItems = [$this->getDiscountLineItem($product, self::UNIT_CODE_ITEM)];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->willReturnCallback(function ($ruleOwners) {
                return $ruleOwners;
            });

        $this->matchingProductsProvider->expects(self::once())
            ->method('getMatchingProducts')
            ->with($promotionSegment, $lineItems)
            ->willReturn([$product]);

        self::assertEquals(
            [$promotion],
            $this->filtrationService->getFilteredRuleOwners(
                [$promotion],
                [ContextDataConverterInterface::LINE_ITEMS => $lineItems]
            )
        );
    }
}
