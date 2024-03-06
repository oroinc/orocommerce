<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Oro\Bundle\CheckoutBundle\Condition\IsMultiShippingEnabledPerLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;

class IsMultiShippingEnabledPerLineItemTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var IsMultiShippingEnabledPerLineItem */
    private $condition;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->condition = new IsMultiShippingEnabledPerLineItem($this->configProvider);
    }

    public function testIsConditionAllowedWhenShippingSelectionByLineItemEnabled(): void
    {
        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);

        self::assertTrue($this->condition->evaluate([]));
    }

    public function testIsConditionAllowedWhenShippingSelectionByLineItemDisabled(): void
    {
        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);

        self::assertFalse($this->condition->evaluate([]));
    }

    public function testGetName(): void
    {
        self::assertEquals('is_multishipping_enabled_per_line_item', $this->condition->getName());
    }

    public function testToArray(): void
    {
        self::assertEquals(
            ['@is_multishipping_enabled_per_line_item' => null],
            $this->condition->toArray()
        );
    }

    public function testCompile(): void
    {
        self::assertEquals(
            '$expressionFactory->create(\'is_multishipping_enabled_per_line_item\', [])',
            $this->condition->compile('$expressionFactory')
        );
    }
}
