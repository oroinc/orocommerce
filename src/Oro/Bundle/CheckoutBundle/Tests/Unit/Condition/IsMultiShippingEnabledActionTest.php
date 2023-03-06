<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Oro\Bundle\CheckoutBundle\Condition\IsMultiShippingEnabled;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;

class IsMultiShippingEnabledActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var IsMultiShippingEnabled */
    private $condition;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->condition = new IsMultiShippingEnabled($this->configProvider);
    }

    public function testIsConditionAllowedIsTrue()
    {
        $this->configProvider->expects($this->once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);

        $this->condition->initialize([]);
        $this->assertTrue($this->condition->evaluate([]));
    }

    public function testIsConditionAllowedIsFalse()
    {
        $this->configProvider->expects($this->once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);

        $this->condition->initialize([]);
        $this->assertFalse($this->condition->evaluate([]));
    }

    public function testGetName()
    {
        $this->assertEquals('is_multishipping_enabled', $this->condition->getName());
    }

    public function testToArray()
    {
        $this->assertEquals(['@is_multishipping_enabled' => null], $this->condition->toArray());
    }

    public function testCompile()
    {
        $this->assertEquals(
            '$expressionFactory->create(\'is_multishipping_enabled\', [])',
            $this->condition->compile('$expressionFactory')
        );
    }
}
