<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountProductUnitCodeAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\PromotionBundle\Provider\MatchingProductsProvider;
use Oro\Bundle\PromotionBundle\RuleFiltration\MatchingItemsFiltrationService;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\EntityTrait;

class MatchingItemsFiltrationServiceTest extends \PHPUnit_Framework_TestCase
{
    const UNIT_CODE_ITEM = 'item';
    const UNIT_CODE_SET = 'set';

    use EntityTrait;

    /**
     * @var RuleFiltrationServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filtrationService;

    /**
     * @var MatchingProductsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $matchingProductsProvider;

    /**
     * @var MatchingItemsFiltrationService
     */
    private $matchingItemsFiltrationService;

    protected function setUp()
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
        $firstPromotion = new Promotion();
        $secondPromotion = new Promotion();
        $ruleOwners = [$firstPromotion, $secondPromotion];

        $this->configureFiltrationService();

        $this->matchingProductsProvider
            ->expects($this->never())
            ->method('getMatchingProducts');

        $this->assertSame($ruleOwners, $this->matchingItemsFiltrationService->getFilteredRuleOwners($ruleOwners, []));
    }

    public function testGetFilteredRuleOwnersWhenNotPromotionRuleOwnersGiven()
    {
        $firstRuleOwner = $this->createMock(RuleOwnerInterface::class);
        $secondRuleOwner = $this->createMock(RuleOwnerInterface::class);

        $ruleOwners = [$firstRuleOwner, $secondRuleOwner];
        $product = new Product();
        $lineItems = [(new DiscountLineItem())->setProduct($product)];

        $this->configureFiltrationService();

        $this->matchingProductsProvider
            ->expects($this->never())
            ->method('getMatchingProducts');

        $this->assertEmpty(
            $this->matchingItemsFiltrationService->getFilteredRuleOwners($ruleOwners, ['lineItems' => $lineItems])
        );
    }

    public function testGetFilteredRuleOwners()
    {
        $firstPromotionSegment = new Segment();
        $firstPromotion = $this->createPromotion($firstPromotionSegment, self::UNIT_CODE_SET);
        $secondPromotionSegment = new Segment();
        $secondPromotion = $this->createPromotion($secondPromotionSegment, self::UNIT_CODE_ITEM);
        $thirdPromotionSegment = new Segment();
        $thirdPromotion = $this->createPromotion($thirdPromotionSegment, self::UNIT_CODE_ITEM);
        $fourthPromotionSegment = new Segment();
        $promotionWithNotUnitAwareDiscountAndWithoutProducts = $this->createPromotion($fourthPromotionSegment);

        $ruleOwners = [
            $firstPromotion,
            $secondPromotion,
            $thirdPromotion,
            $promotionWithNotUnitAwareDiscountAndWithoutProducts,
        ];

        $product = new Product();
        $lineItems = [
            (new DiscountLineItem())
                ->setProduct($product)
                ->setProductUnitCode(self::UNIT_CODE_ITEM)
        ];

        $this->configureFiltrationService();

        $this->matchingProductsProvider
            ->expects($this->exactly(4))
            ->method('getMatchingProducts')
            ->willReturnMap([
                [$firstPromotionSegment, $lineItems, [$product]],
                [$secondPromotionSegment, $lineItems, []],
                [$thirdPromotionSegment, $lineItems, [$product]],
                [$fourthPromotionSegment, $lineItems, []]
            ]);

        $this->assertEquals(
            [$thirdPromotion],
            $this->matchingItemsFiltrationService->getFilteredRuleOwners($ruleOwners, ['lineItems' => $lineItems])
        );
    }

    /**
     * @param Segment $segment
     * @param string|null $productUnitCode
     * @return Promotion
     */
    private function createPromotion(Segment $segment, $productUnitCode = null)
    {
        $options = [];
        if ($productUnitCode) {
            $options[DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE] = $productUnitCode;
        }
        $discountConfiguration = $this->getEntity(DiscountConfiguration::class, ['options' => $options]);

        /** @var Promotion|\PHPUnit_Framework_MockObject_MockObject $promotion */
        $promotion = $this->getEntity(Promotion::class, [
            'productsSegment' => $segment,
            'discountConfiguration' => $discountConfiguration
        ]);

        return $promotion;
    }

    private function configureFiltrationService()
    {
        $this->filtrationService
            ->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->willReturnCallback(function ($ruleOwners) {
                return $ruleOwners;
            });
    }
}
