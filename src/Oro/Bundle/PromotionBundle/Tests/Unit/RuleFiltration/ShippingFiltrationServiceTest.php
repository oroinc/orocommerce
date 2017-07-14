<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\RuleFiltration\ShippingFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class ShippingFiltrationServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RuleFiltrationServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filtrationService;

    /**
     * @var ShippingFiltrationService
     */
    protected $shippingFiltrationService;

    protected function setUp()
    {
        $this->filtrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->shippingFiltrationService = new ShippingFiltrationService($this->filtrationService);
    }

    public function testReturnRuleOwnersWithoutChangesForNotSupportedContext()
    {
        $notSupportedRuleOwner = new \stdClass();
        $contextWithoutRequiredInformation = [];
        $filteredRuleOwners = [$notSupportedRuleOwner];

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with($filteredRuleOwners, $contextWithoutRequiredInformation)
            ->willReturn([]);

        $this->shippingFiltrationService->getFilteredRuleOwners(
            $filteredRuleOwners,
            $contextWithoutRequiredInformation
        );
    }

    public function testFilterRuleOwnersWithNotSupportedClass()
    {
        $notSupportedRuleOwner = new \stdClass();
        $context = [
            ContextDataConverterInterface::SHIPPING_METHOD => 'shipping method',
            ContextDataConverterInterface::SHIPPING_METHOD_TYPE => 'shipping method type',
        ];

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([], $context)
            ->willReturn([]);

        $this->shippingFiltrationService->getFilteredRuleOwners(
            [$notSupportedRuleOwner],
            $context
        );
    }

    public function testFilterShippingPromotionsWithNotMatchedShippingOptions()
    {
        $promotion = new Promotion();
        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType('shipping');
        $discountConfiguration->setOptions([
            ShippingDiscount::SHIPPING_OPTIONS => [
                ShippingDiscount::SHIPPING_METHOD => 'not matched shipping method',
                ShippingDiscount::SHIPPING_METHOD_TYPE => 'not matched shipping method type',
            ]
        ]);
        $promotion->setDiscountConfiguration($discountConfiguration);
        $context = [
            ContextDataConverterInterface::SHIPPING_METHOD => 'shipping method',
            ContextDataConverterInterface::SHIPPING_METHOD_TYPE => 'shipping method type',
        ];

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
        $promotion = new Promotion();
        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType('shipping');
        $discountConfiguration->setOptions([
            ShippingDiscount::SHIPPING_OPTIONS => [
                ShippingDiscount::SHIPPING_METHOD => 'shipping method',
                ShippingDiscount::SHIPPING_METHOD_TYPE => 'shipping method type',
            ]
        ]);
        $promotion->setDiscountConfiguration($discountConfiguration);
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
        $promotion = new Promotion();
        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType('order');
        $promotion->setDiscountConfiguration($discountConfiguration);
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
}
