<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Checker;

use Oro\Bundle\ShippingBundle\Checker\ShippingMethodEnabledByIdentifierCheckerInterface;
use Oro\Bundle\ShippingBundle\Checker\ShippingRuleEnabledChecker;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

class ShippingRuleEnabledCheckerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ShippingMethodEnabledByIdentifierCheckerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $methodEnabledChecker;

    /** @var ShippingRuleEnabledChecker */
    private $ruleChecker;

    protected function setUp()
    {
        $this->methodEnabledChecker = $this->createMock(
            ShippingMethodEnabledByIdentifierCheckerInterface::class
        );

        $this->ruleChecker = new ShippingRuleEnabledChecker($this->methodEnabledChecker);
    }

    public function testCanBeEnabledForOneEnabledMethod()
    {
        $this->methodEnabledChecker->expects(static::at(1))
            ->method('isEnabled')
            ->willReturn(true);

        $rule = $this->getRuleMock();

        static::assertTrue($this->ruleChecker->canBeEnabled($rule));
    }

    public function testCanBeEnabledForNoEnabledMethods()
    {
        $rule = $this->getRuleMock();

        static::assertFalse($this->ruleChecker->canBeEnabled($rule));
    }

    /**
     * @return ShippingMethodsConfigsRule|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getRuleMock()
    {
        $rule = $this->createMock(ShippingMethodsConfigsRule::class);
        $rule->expects(static::any())
            ->method('getMethodConfigs')
            ->willReturn([
                new ShippingMethodConfig(), new ShippingMethodConfig()
            ]);

        return $rule;
    }
}
