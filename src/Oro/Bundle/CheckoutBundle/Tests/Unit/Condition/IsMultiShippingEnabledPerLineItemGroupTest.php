<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Oro\Bundle\CheckoutBundle\Condition\IsMultiShippingEnabledPerLineItemGroup;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;

class IsMultiShippingEnabledPerLineItemGroupTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var IsMultiShippingEnabledPerLineItemGroup */
    private $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->condition = new IsMultiShippingEnabledPerLineItemGroup($this->configProvider);
    }

    public function testIsConditionAllowedWhenLineItemsGroupingEnabledAndShippingSelectionByLineItemDisabled(): void
    {
        $this->configProvider->expects(self::once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(true);
        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);

        self::assertTrue($this->condition->evaluate([]));
    }

    public function testIsConditionAllowedWhenBothLineItemsGroupingAndShippingSelectionByLineItemEnabled(): void
    {
        $this->configProvider->expects(self::once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(true);
        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);

        self::assertFalse($this->condition->evaluate([]));
    }

    public function testIsConditionAllowedWhenLineItemsGroupingDisabled(): void
    {
        $this->configProvider->expects(self::once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(false);
        $this->configProvider->expects(self::never())
            ->method('isShippingSelectionByLineItemEnabled');

        self::assertFalse($this->condition->evaluate([]));
    }

    public function testGetName(): void
    {
        self::assertEquals('is_multishipping_enabled_per_line_item_group', $this->condition->getName());
    }

    public function testToArray(): void
    {
        self::assertEquals(
            ['@is_multishipping_enabled_per_line_item_group' => null],
            $this->condition->toArray()
        );
    }

    public function testCompile(): void
    {
        self::assertEquals(
            '$expressionFactory->create(\'is_multishipping_enabled_per_line_item_group\', [])',
            $this->condition->compile('$expressionFactory')
        );
    }
}
