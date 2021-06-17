<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Checker;

use Oro\Bundle\ShippingBundle\Checker\ShippingMethodEnabledByIdentifierCheckerInterface;
use Oro\Bundle\ShippingBundle\Checker\ShippingRuleEnabledChecker;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

class ShippingRuleEnabledCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodEnabledByIdentifierCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $methodEnabledChecker;

    /** @var ShippingRuleEnabledChecker */
    private $ruleChecker;

    protected function setUp(): void
    {
        $this->methodEnabledChecker = $this->createMock(
            ShippingMethodEnabledByIdentifierCheckerInterface::class
        );

        $this->ruleChecker = new ShippingRuleEnabledChecker($this->methodEnabledChecker);
    }

    public function testCanBeEnabledForOneEnabledMethod()
    {
        $this->methodEnabledChecker->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);

        $rule = $this->getRule();

        self::assertTrue($this->ruleChecker->canBeEnabled($rule));
    }

    public function testCanBeEnabledForNoEnabledMethods()
    {
        $rule = $this->getRule();

        self::assertFalse($this->ruleChecker->canBeEnabled($rule));
    }

    private function getRule(): ShippingMethodsConfigsRule
    {
        $rule = $this->createMock(ShippingMethodsConfigsRule::class);
        $rule->expects(self::any())
            ->method('getMethodConfigs')
            ->willReturn([new ShippingMethodConfig(), new ShippingMethodConfig()]);

        return $rule;
    }
}
