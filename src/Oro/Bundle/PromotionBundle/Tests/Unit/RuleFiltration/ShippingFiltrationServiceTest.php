<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\RuleFiltration\ShippingFiltrationService;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class ShippingFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $baseFiltrationService;

    /** @var ShippingFiltrationService */
    private $filtrationService;

    #[\Override]
    protected function setUp(): void
    {
        $this->baseFiltrationService = $this->createMock(RuleFiltrationServiceInterface::class);

        $this->filtrationService = new ShippingFiltrationService($this->baseFiltrationService);
    }

    private function getPromotion(DiscountConfiguration $discountConfiguration): PromotionDataInterface
    {
        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects(self::once())
            ->method('getDiscountConfiguration')
            ->willReturn($discountConfiguration);

        return $promotion;
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
                ['skip_filters' => [ShippingFiltrationService::class => true]]
            )
        );
    }

    public function testFilterRuleOwnersWithNotSupportedClass(): void
    {
        $context = [
            ContextDataConverterInterface::SHIPPING_METHOD => 'shipping method',
            ContextDataConverterInterface::SHIPPING_METHOD_TYPE => 'shipping method type'
        ];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with([], $context)
            ->willReturn([]);

        self::assertSame([], $this->filtrationService->getFilteredRuleOwners([new \stdClass()], $context));
    }

    public function testFilterShippingPromotionsWhenNoShippingOptions(): void
    {
        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType('shipping');

        $promotion = $this->getPromotion($discountConfiguration);

        $context = [
            ContextDataConverterInterface::SHIPPING_METHOD => 'shipping method',
            ContextDataConverterInterface::SHIPPING_METHOD_TYPE => 'shipping method type'
        ];

        $filteredRuleOwners = [$promotion];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with([], $context)
            ->willReturn($filteredRuleOwners);

        self::assertSame(
            $filteredRuleOwners,
            $this->filtrationService->getFilteredRuleOwners([$promotion], $context)
        );
    }

    public function testFilterShippingPromotionsWhenNoShippingMethodInShippingOptions(): void
    {
        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType('shipping');
        $discountConfiguration->setOptions([
            ShippingDiscount::SHIPPING_OPTIONS => [
                ShippingDiscount::SHIPPING_METHOD_TYPE => 'shipping method type'
            ]
        ]);

        $promotion = $this->getPromotion($discountConfiguration);

        $context = [
            ContextDataConverterInterface::SHIPPING_METHOD => 'shipping method',
            ContextDataConverterInterface::SHIPPING_METHOD_TYPE => 'shipping method type'
        ];

        $filteredRuleOwners = [$promotion];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with([], $context)
            ->willReturn($filteredRuleOwners);

        self::assertSame(
            $filteredRuleOwners,
            $this->filtrationService->getFilteredRuleOwners([$promotion], $context)
        );
    }

    public function testFilterShippingPromotionsWhenNoShippingMethodTypeInShippingOptions(): void
    {
        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType('shipping');
        $discountConfiguration->setOptions([
            ShippingDiscount::SHIPPING_OPTIONS => [
                ShippingDiscount::SHIPPING_METHOD => 'shipping method'
            ]
        ]);

        $promotion = $this->getPromotion($discountConfiguration);

        $context = [
            ContextDataConverterInterface::SHIPPING_METHOD => 'shipping method',
            ContextDataConverterInterface::SHIPPING_METHOD_TYPE => 'shipping method type'
        ];

        $filteredRuleOwners = [$promotion];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with([], $context)
            ->willReturn($filteredRuleOwners);

        self::assertSame(
            $filteredRuleOwners,
            $this->filtrationService->getFilteredRuleOwners([$promotion], $context)
        );
    }

    /**
     * @dataProvider contextDataProvider
     */
    public function testFilterShippingPromotionsWithNotMatchedShippingOptions(array $context): void
    {
        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType('shipping');
        $discountConfiguration->setOptions([
            ShippingDiscount::SHIPPING_OPTIONS => [
                ShippingDiscount::SHIPPING_METHOD => 'not matched shipping method',
                ShippingDiscount::SHIPPING_METHOD_TYPE => 'not matched shipping method type'
            ]
        ]);

        $promotion = $this->getPromotion($discountConfiguration);

        $filteredRuleOwners = [$promotion];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with([], $context)
            ->willReturn($filteredRuleOwners);

        self::assertSame(
            $filteredRuleOwners,
            $this->filtrationService->getFilteredRuleOwners([$promotion], $context)
        );
    }

    public function contextDataProvider(): array
    {
        return [
            'empty context' => [
                'context' => []
            ],
            'filled context' => [
                'context' => [
                    ContextDataConverterInterface::SHIPPING_METHOD => 'shipping method',
                    ContextDataConverterInterface::SHIPPING_METHOD_TYPE => 'shipping method type'
                ]
            ]
        ];
    }

    public function testAllowShippingPromotionsWithMatchedShippingOptions(): void
    {
        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType('shipping');
        $discountConfiguration->setOptions([
            ShippingDiscount::SHIPPING_OPTIONS => [
                ShippingDiscount::SHIPPING_METHOD => 'shipping method',
                ShippingDiscount::SHIPPING_METHOD_TYPE => 'shipping method type'
            ]
        ]);

        $promotion = $this->getPromotion($discountConfiguration);

        $context = [
            ContextDataConverterInterface::SHIPPING_METHOD => 'shipping method',
            ContextDataConverterInterface::SHIPPING_METHOD_TYPE => 'shipping method type'
        ];

        $filteredRuleOwners = [$promotion];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with([$promotion], $context)
            ->willReturn($filteredRuleOwners);

        self::assertSame(
            $filteredRuleOwners,
            $this->filtrationService->getFilteredRuleOwners([$promotion], $context)
        );
    }

    public function testAllowNotShippingPromotions(): void
    {
        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType('order');

        $promotion = $this->getPromotion($discountConfiguration);

        $context = [
            ContextDataConverterInterface::SHIPPING_METHOD => 'shipping method',
            ContextDataConverterInterface::SHIPPING_METHOD_TYPE => 'shipping method type'
        ];

        $filteredRuleOwners = [$promotion];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with([$promotion], $context)
            ->willReturn($filteredRuleOwners);

        self::assertSame(
            $filteredRuleOwners,
            $this->filtrationService->getFilteredRuleOwners([$promotion], $context)
        );
    }
}
