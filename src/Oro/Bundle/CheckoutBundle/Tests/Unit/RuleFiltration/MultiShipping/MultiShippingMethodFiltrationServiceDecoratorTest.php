<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\RuleFiltration\MultiShipping;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\CheckoutBundle\RuleFiltration\MultiShipping\MultiShippingMethodFiltrationServiceDecorator;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

class MultiShippingMethodFiltrationServiceDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $filtrationService;

    /** @var DefaultMultipleShippingMethodProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $multiShippingMethodsProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var MultiShippingMethodFiltrationServiceDecorator */
    private $multiShippingFiltrationService;

    protected function setUp(): void
    {
        $this->filtrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->multiShippingMethodsProvider = $this->createMock(DefaultMultipleShippingMethodProvider::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->multiShippingFiltrationService = new MultiShippingMethodFiltrationServiceDecorator(
            $this->filtrationService,
            $this->multiShippingMethodsProvider,
            $this->configProvider
        );
    }

    public function testGetFilteredRuleOwners()
    {
        $this->configProvider->expects($this->once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);

        $this->multiShippingMethodsProvider->expects($this->never())
            ->method('hasShippingMethods');

        $this->multiShippingMethodsProvider->expects($this->once())
            ->method('getShippingMethods')
            ->willReturn(['multi_shipping_1']);

        $rule1 = $this->createShippingMethodConfigRule('multi_shipping_1');
        $rule2 = $this->createShippingMethodConfigRule('flat_rate_1');
        $rule3 = new \stdClass();

        $ruleOwners = [$rule1, $rule2, $rule3];

        $ruleOwnersWithoutMultiShippingRules = [1 => $rule2, 2 => $rule3];

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with($ruleOwnersWithoutMultiShippingRules, [])
            ->willReturn($ruleOwnersWithoutMultiShippingRules);

        $result = $this->multiShippingFiltrationService->getFilteredRuleOwners($ruleOwners, []);
        $this->assertCount(2, $result);
        $ruleConfig1 = $result[1];
        $ruleConfig2 = $result[2];

        $method = $ruleConfig1->getMethodConfigs()[0];
        $this->assertEquals('flat_rate_1', $method);

        $this->assertInstanceOf(\stdClass::class, $ruleConfig2);
    }

    public function testGetFilteredRuleOwnersWhenMultiShippingDisabledButMethodsConfigured()
    {
        $this->configProvider->expects($this->once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);

        $this->multiShippingMethodsProvider->expects($this->once())
            ->method('hasShippingMethods')
            ->willReturn(true);

        $this->multiShippingMethodsProvider->expects($this->once())
            ->method('getShippingMethods')
            ->willReturn(['multi_shipping_1']);

        $rule1 = $this->createShippingMethodConfigRule('multi_shipping_1');
        $rule2 = $this->createShippingMethodConfigRule('flat_rate_1');

        $ruleOwners = [$rule1, $rule2];

        $ruleOwnersWithoutMultiShippingRules = [1 => $rule2];

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with($ruleOwnersWithoutMultiShippingRules, [])
            ->willReturn($ruleOwnersWithoutMultiShippingRules);

        $result = $this->multiShippingFiltrationService->getFilteredRuleOwners($ruleOwners, []);
        $this->assertCount(1, $result);
        $ruleConfig = reset($result);

        $method = $ruleConfig->getMethodConfigs()[0];
        $this->assertEquals('flat_rate_1', $method);
    }

    public function testGetFilteredRuleOwnersWhenFiltrationNotAllowed()
    {
        $this->configProvider->expects($this->once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);

        $this->multiShippingMethodsProvider->expects($this->once())
            ->method('hasShippingMethods')
            ->willReturn(false);

        $this->multiShippingMethodsProvider->expects($this->never())
            ->method('getShippingMethods');

        $rule1 = $this->createShippingMethodConfigRule('flat_rate_2');
        $rule2 = $this->createShippingMethodConfigRule('flat_rate_1');

        $ruleOwners = [$rule1, $rule2];

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with($ruleOwners, [])
            ->willReturn($ruleOwners);

        $result = $this->multiShippingFiltrationService->getFilteredRuleOwners($ruleOwners, []);
        $this->assertCount(2, $result);
    }

    private function createShippingMethodConfigRule(string $shippingMethod): ShippingMethodsConfigsRule
    {
        $methodConfigRule = new ShippingMethodsConfigsRule();
        $shippingMethodConfig = new ShippingMethodConfig();
        $shippingMethodConfig->setMethod($shippingMethod);
        $methodConfigRule->addMethodConfig($shippingMethodConfig);

        return $methodConfigRule;
    }
}