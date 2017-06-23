<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\PromotionBundle\Provider\MatchingProductsProvider;
use Oro\Bundle\PromotionBundle\RuleFiltration\MatchingItemsFiltrationService;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\EntityTrait;

class MatchingItemsFiltrationServiceTest extends \PHPUnit_Framework_TestCase
{
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
        $this->matchingProductsProvider = $this->getMockBuilder(MatchingProductsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

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
            ->method('hasMatchingProducts');

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
            ->method('hasMatchingProducts');

        $this->assertEmpty(
            $this->matchingItemsFiltrationService->getFilteredRuleOwners($ruleOwners, ['lineItems' => $lineItems])
        );
    }

    public function testGetFilteredRuleOwners()
    {
        $firstPromotionSegment = new Segment();
        $firstPromotion = $this->getEntity(Promotion::class, ['productsSegment' => $firstPromotionSegment]);
        $secondPromotionSegment = new Segment();
        $secondPromotion = $this->getEntity(Promotion::class, ['productsSegment' => $secondPromotionSegment]);
        $thirdPromotionSegment = new Segment();
        $thirdPromotion = $this->getEntity(Promotion::class, ['productsSegment' => $thirdPromotionSegment]);

        $ruleOwners = [$firstPromotion, $secondPromotion, $thirdPromotion];
        $product = new Product();
        $lineItems = [(new DiscountLineItem())->setProduct($product)];

        $this->configureFiltrationService();

        $this->matchingProductsProvider
            ->expects($this->exactly(3))
            ->method('hasMatchingProducts')
            ->willReturnMap([
                [$firstPromotionSegment, $lineItems, true],
                [$secondPromotionSegment, $lineItems, false],
                [$thirdPromotionSegment, $lineItems, true]
            ]);

        $this->assertEquals(
            [$firstPromotion, $thirdPromotion],
            $this->matchingItemsFiltrationService->getFilteredRuleOwners($ruleOwners, ['lineItems' => $lineItems])
        );
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
