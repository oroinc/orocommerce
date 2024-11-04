<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConfigProvider */
    private $configProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->configProvider = new ConfigProvider($this->configManager);
    }

    public function testIsLineItemsGroupingEnabledWhenEnabled(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.enable_line_item_grouping')
            ->willReturn(true);

        self::assertTrue($this->configProvider->isLineItemsGroupingEnabled());
    }

    public function testIsLineItemsGroupingEnabledWhenDisabled(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.enable_line_item_grouping')
            ->willReturn(false);

        self::assertFalse($this->configProvider->isLineItemsGroupingEnabled());
    }

    public function testGetGroupLineItemsByField(): void
    {
        $configValue = 'product.owner';
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.group_line_items_by')
            ->willReturn($configValue);

        self::assertEquals($configValue, $this->configProvider->getGroupLineItemsByField());
    }

    public function testIsCreateSubOrdersForEachGroupEnabled(): void
    {
        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_checkout.enable_line_item_grouping', false, false, null, true],
                ['oro_checkout.create_suborders_for_each_group', false, false, null, true]
            ]);

        self::assertTrue($this->configProvider->isCreateSubOrdersForEachGroupEnabled());
    }

    public function testIsCreateSubOrdersForEachGroupEnabledIfConfigDisabled(): void
    {
        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_checkout.enable_line_item_grouping', false, false, null, true],
                ['oro_checkout.create_suborders_for_each_group', false, false, null, false]
            ]);

        self::assertFalse($this->configProvider->isCreateSubOrdersForEachGroupEnabled());
    }

    public function testIsCreateSubOrdersForEachGroupIfGroupingConfigDisabled(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.enable_line_item_grouping')
            ->willReturn(false);

        self::assertFalse($this->configProvider->isCreateSubOrdersForEachGroupEnabled());
    }

    public function testIsShowSubordersInOrderHistoryEnabled(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.show_suborders_in_order_history')
            ->willReturn(true);

        self::assertTrue($this->configProvider->isShowSubordersInOrderHistoryEnabled());
    }

    public function testIsShowSubordersInOrderHistoryEnabledIfConfigDisabled(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.show_suborders_in_order_history')
            ->willReturn(false);

        self::assertFalse($this->configProvider->isShowSubordersInOrderHistoryEnabled());
    }

    /**
     * @dataProvider isShowMainOrdersInOrderHistoryEnabledProvider
     */
    public function testIsShowMainOrdersAndSubOrdersInOrderHistoryEnabled(array $parameters, bool $expected): void
    {
        $this->configManager->expects(self::any())
            ->method('get')
            ->willReturnCallback(function ($parameterName) use ($parameters) {
                return $parameters[$parameterName];
            });

        self::assertEquals($expected, $this->configProvider->isShowMainOrdersAndSubOrdersInOrderHistoryEnabled());
    }

    public function isShowMainOrdersInOrderHistoryEnabledProvider(): array
    {
        return [
            [
                'parameters' => [
                    'oro_checkout.show_suborders_in_order_history' => true,
                    'oro_checkout.show_main_orders_in_order_history' => true,
                ],
                'expected' => true,
            ],
            [
                'parameters' => [
                    'oro_checkout.show_suborders_in_order_history' => false,
                    'oro_checkout.show_main_orders_in_order_history' => false,
                ],
                'expected' => false,
            ],
            [
                'parameters' => [
                    'oro_checkout.show_suborders_in_order_history' => false,
                    'oro_checkout.show_main_orders_in_order_history' => true,
                ],
                'expected' => false,
            ],
            [
                'parameters' => [
                    'oro_checkout.show_suborders_in_order_history' => true,
                    'oro_checkout.show_main_orders_in_order_history' => false,
                ],
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider isShowMainOrderInOrderHistoryDisabledProvider
     */
    public function testIsShowMainOrderInOrderHistoryDisabled(array $parameters, bool $expected): void
    {
        $this->configManager->expects(self::any())
            ->method('get')
            ->willReturnCallback(function ($parameterName) use ($parameters) {
                return $parameters[$parameterName];
            });

        self::assertEquals($expected, $this->configProvider->isShowMainOrderInOrderHistoryDisabled());
    }

    public function isShowMainOrderInOrderHistoryDisabledProvider(): array
    {
        return [
            [
                'parameters' => [
                    'oro_checkout.show_suborders_in_order_history' => true,
                    'oro_checkout.show_main_orders_in_order_history' => true,
                ],
                'expected' => false,
            ],
            [
                'parameters' => [
                    'oro_checkout.show_suborders_in_order_history' => false,
                    'oro_checkout.show_main_orders_in_order_history' => false,
                ],
                'expected' => false,
            ],
            [
                'parameters' => [
                    'oro_checkout.show_suborders_in_order_history' => false,
                    'oro_checkout.show_main_orders_in_order_history' => true,
                ],
                'expected' => false,
            ],
            [
                'parameters' => [
                    'oro_checkout.show_suborders_in_order_history' => true,
                    'oro_checkout.show_main_orders_in_order_history' => false,
                ],
                'expected' => true,
            ],
        ];
    }

    public function testIsShippingSelectionByLineItemEnabled(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.enable_shipping_method_selection_per_line_item')
            ->willReturn(true);

        self::assertTrue($this->configProvider->isShippingSelectionByLineItemEnabled());
    }

    public function testIsShippingSelectionByLineItemDisabled(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.enable_shipping_method_selection_per_line_item')
            ->willReturn(false);

        self::assertFalse($this->configProvider->isShippingSelectionByLineItemEnabled());
    }

    public function testIsMultiShippingEnabled(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.enable_line_item_grouping')
            ->willReturn(true);

        self::assertTrue($this->configProvider->isMultiShippingEnabled());
    }

    public function testIsMultiShippingEnabledWithoutGroupingOfLineItems(): void
    {
        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_checkout.enable_line_item_grouping', false, false, null, false],
                ['oro_checkout.enable_shipping_method_selection_per_line_item', false, false, null, true]
            ]);

        self::assertTrue($this->configProvider->isMultiShippingEnabled());
    }

    public function testIsMultiShippingDisabled(): void
    {
        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_checkout.enable_line_item_grouping', false, false, null, false],
                ['oro_checkout.enable_shipping_method_selection_per_line_item', false, false, null, false]
            ]);

        self::assertFalse($this->configProvider->isMultiShippingEnabled());
    }
}
