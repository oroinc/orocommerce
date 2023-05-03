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

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configProvider = new ConfigProvider($this->configManager);
    }

    public function testIsLineItemsGroupingEnabled()
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_checkout.enable_shipping_method_selection_per_line_item', false, false, null, true],
                ['oro_checkout.enable_line_item_grouping', false, false, null, true],
            ]);

        $this->assertTrue($this->configProvider->isLineItemsGroupingEnabled());
    }

    public function testIsLineItemsGroupingEnabledIfMultiShippingDisabled()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.enable_shipping_method_selection_per_line_item')
            ->willReturn(false);

        $this->assertFalse($this->configProvider->isLineItemsGroupingEnabled());
    }

    public function testIsLineItemsGroupingEnabledIfConfigValueDisabled()
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_checkout.enable_shipping_method_selection_per_line_item', false, false, null, true],
                ['oro_checkout.enable_line_item_grouping', false, false, null, false],
            ]);


        $this->assertFalse($this->configProvider->isLineItemsGroupingEnabled());
    }

    public function testGetGroupLineItemsByField()
    {
        $configValue = 'product.owner';
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.group_line_items_by')
            ->willReturn($configValue);

        $this->assertEquals($configValue, $this->configProvider->getGroupLineItemsByField());
    }

    public function testIsCreateSubOrdersForEachGroupEnabled()
    {
        $this->configManager->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                ['oro_checkout.enable_shipping_method_selection_per_line_item', false, false, null, true],
                ['oro_checkout.enable_line_item_grouping', false, false, null, true],
                ['oro_checkout.create_suborders_for_each_group', false, false, null, true]
            ]);

        $this->assertTrue($this->configProvider->isCreateSubOrdersForEachGroupEnabled());
    }

    public function testIsCreateSubOrdersForEachGroupEnabledIfConfigDisabled()
    {
        $this->configManager->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                ['oro_checkout.enable_shipping_method_selection_per_line_item', false, false, null, true],
                ['oro_checkout.enable_line_item_grouping', false, false, null, true],
                ['oro_checkout.create_suborders_for_each_group', false, false, null, false]
            ]);

        $this->assertFalse($this->configProvider->isCreateSubOrdersForEachGroupEnabled());
    }

    public function testIsCreateSubOrdersForEachGroupIfMultiShippingDisabled()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.enable_shipping_method_selection_per_line_item')
            ->willReturn(false);

        $this->assertFalse($this->configProvider->isCreateSubOrdersForEachGroupEnabled());
    }

    public function testIsCreateSubOrdersForEachGroupIfGroupingConfigDisabled()
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_checkout.enable_shipping_method_selection_per_line_item', false, false, null, true],
                ['oro_checkout.enable_line_item_grouping', false, false, null, false],
            ]);

        $this->assertFalse($this->configProvider->isCreateSubOrdersForEachGroupEnabled());
    }

    public function testIsShowSubordersInOrderHistoryEnabled()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.show_suborders_in_order_history')
            ->willReturn(true);

        $this->assertTrue($this->configProvider->isShowSubordersInOrderHistoryEnabled());
    }

    public function testIsShowSubordersInOrderHistoryEnabledIfConfigDisabled()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.show_suborders_in_order_history')
            ->willReturn(false);

        $this->assertFalse($this->configProvider->isShowSubordersInOrderHistoryEnabled());
    }

    /**
     * @dataProvider isShowMainOrdersInOrderHistoryEnabledProvider
     */
    public function testIsShowMainOrdersAndSubOrdersInOrderHistoryEnabled($parameters, $expected)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($parameterName) use ($parameters) {
                return $parameters[$parameterName];
            });

        $this->assertEquals($expected, $this->configProvider->isShowMainOrdersAndSubOrdersInOrderHistoryEnabled());
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
    public function testIsShowMainOrderInOrderHistoryDisabled($parameters, $expected)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($parameterName) use ($parameters) {
                return $parameters[$parameterName];
            });

        $this->assertEquals($expected, $this->configProvider->isShowMainOrderInOrderHistoryDisabled());
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

    public function testIsShippingSelectionByLineItemEnabled()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.enable_shipping_method_selection_per_line_item')
            ->willReturn(true);

        $this->assertTrue($this->configProvider->isShippingSelectionByLineItemEnabled());
    }

    public function testIsShippingSelectionByLineItemDisabled()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.enable_shipping_method_selection_per_line_item')
            ->willReturn(false);

        $this->assertFalse($this->configProvider->isShippingSelectionByLineItemEnabled());
    }
}
