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

    private function getRule(array $methods): ShippingMethodsConfigsRule
    {
        $rule = new ShippingMethodsConfigsRule();
        foreach ($methods as $method) {
            $rule->addMethodConfig($this->getMethodConfig($method));
        }

        return $rule;
    }

    private function getMethodConfig(string $method): ShippingMethodConfig
    {
        $methodConfig = new ShippingMethodConfig();
        $methodConfig->setMethod($method);

        return $methodConfig;
    }

    public function testCanBeEnabledForOneEnabledMethod(): void
    {
        $rule = $this->getRule(['method_1', 'method_2', 'method_3']);

        $this->methodEnabledChecker->expects(self::exactly(2))
            ->method('isEnabled')
            ->withConsecutive(['method_1'], ['method_2'])
            ->willReturnOnConsecutiveCalls(false, true);

        self::assertTrue($this->ruleChecker->canBeEnabled($rule));
    }

    public function testCanBeEnabledForNoEnabledMethods(): void
    {
        $rule = $this->getRule(['method_1', 'method_2']);

        $this->methodEnabledChecker->expects(self::exactly(2))
            ->method('isEnabled')
            ->withConsecutive(['method_1'], ['method_2'])
            ->willReturn(false);

        self::assertFalse($this->ruleChecker->canBeEnabled($rule));
    }
}
