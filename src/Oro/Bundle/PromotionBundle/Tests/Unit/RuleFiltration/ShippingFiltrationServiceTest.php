<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\RuleFiltration\ShippingFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class ShippingFiltrationServiceTest extends AbstractSkippableFiltrationServiceTest
{
    /**
     * @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $filtrationService;

    /**
     * @var ShippingFiltrationService
     */
    protected $shippingFiltrationService;

    protected function setUp(): void
    {
        $this->filtrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->shippingFiltrationService = new ShippingFiltrationService($this->filtrationService);
    }

    /**
     * @dataProvider contextDataProvider
     */
    public function testFilterRuleOwnersWithNotSupportedClass(array $context)
    {
        $notSupportedRuleOwner = new \stdClass();

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([], $context)
            ->willReturn([]);

        $this->shippingFiltrationService->getFilteredRuleOwners(
            [$notSupportedRuleOwner],
            $context
        );
    }

    /**
     * @return array
     */
    public function contextDataProvider()
    {
        return [
            'empty context' => [
                'context' => [],
            ],
            'filled context' => [
                'context' => [
                    ContextDataConverterInterface::SHIPPING_METHOD => 'shipping method',
                    ContextDataConverterInterface::SHIPPING_METHOD_TYPE => 'shipping method type',
                ],
            ]
        ];
    }

    /**
     * @dataProvider contextDataProvider
     */
    public function testFilterShippingPromotionsWithNotMatchedShippingOptions(array $context)
    {
        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType('shipping');
        $discountConfiguration->setOptions([
            ShippingDiscount::SHIPPING_OPTIONS => [
                ShippingDiscount::SHIPPING_METHOD => 'not matched shipping method',
                ShippingDiscount::SHIPPING_METHOD_TYPE => 'not matched shipping method type',
            ]
        ]);

        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects($this->any())
            ->method('getDiscountConfiguration')
            ->willReturn($discountConfiguration);

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([], $context)
            ->willReturn([]);

        $this->shippingFiltrationService->getFilteredRuleOwners(
            [$promotion],
            $context
        );
    }

    public function testAllowShippingPromotionsWithMatchedShippingOptions()
    {
        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType('shipping');
        $discountConfiguration->setOptions([
            ShippingDiscount::SHIPPING_OPTIONS => [
                ShippingDiscount::SHIPPING_METHOD => 'shipping method',
                ShippingDiscount::SHIPPING_METHOD_TYPE => 'shipping method type',
            ]
        ]);

        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects($this->any())
            ->method('getDiscountConfiguration')
            ->willReturn($discountConfiguration);

        $context = [
            ContextDataConverterInterface::SHIPPING_METHOD => 'shipping method',
            ContextDataConverterInterface::SHIPPING_METHOD_TYPE => 'shipping method type',
        ];

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([$promotion], $context)
            ->willReturn([]);

        $this->shippingFiltrationService->getFilteredRuleOwners(
            [$promotion],
            $context
        );
    }

    public function testAllowNotShippingPromotions()
    {
        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType('order');

        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects($this->any())
            ->method('getDiscountConfiguration')
            ->willReturn($discountConfiguration);

        $context = [
            ContextDataConverterInterface::SHIPPING_METHOD => 'shipping method',
            ContextDataConverterInterface::SHIPPING_METHOD_TYPE => 'shipping method type',
        ];

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([$promotion], $context)
            ->willReturn([]);

        $this->shippingFiltrationService->getFilteredRuleOwners(
            [$promotion],
            $context
        );
    }

    public function testFilterIsSkippable()
    {
        $this->assertServiceSkipped($this->shippingFiltrationService, $this->filtrationService);
    }
}
