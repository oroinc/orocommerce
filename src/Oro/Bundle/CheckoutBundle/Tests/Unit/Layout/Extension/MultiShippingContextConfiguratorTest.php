<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\CheckoutBundle\Layout\Extension\MultiShippingContextConfigurator;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Component\Layout\LayoutContext;

class MultiShippingContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var MultiShippingContextConfigurator */
    private $contextConfigurator;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->contextConfigurator = new MultiShippingContextConfigurator($this->configProvider);
    }

    /**
     * @dataProvider configureContextDataProvider
     */
    public function testConfigureContextForSupportedWorkflow(
        bool $isMultiShippingEnabled,
        bool $isLineItemsGroupingEnabled,
        bool $isShippingSelectionByLineItemEnabled,
        ?string $multiShippingType
    ): void {
        $this->configProvider->expects(self::any())
            ->method('isMultiShippingEnabled')
            ->willReturn($isMultiShippingEnabled);
        $this->configProvider->expects(self::any())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn($isLineItemsGroupingEnabled);
        $this->configProvider->expects(self::any())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn($isShippingSelectionByLineItemEnabled);

        $context = new LayoutContext();
        $context->set('workflowName', 'b2b_flow_checkout');
        $this->contextConfigurator->configureContext($context);

        self::assertSame($isShippingSelectionByLineItemEnabled, $context->get('multi_shipping_enabled'));
        self::assertSame($isLineItemsGroupingEnabled, $context->get('grouped_line_items_enabled'));
        self::assertSame($multiShippingType, $context->get('multi_shipping_type'));
    }

    /**
     * @dataProvider configureContextDataProvider
     */
    public function testConfigureContextForNotSupportedWorkflow(
        bool $isMultiShippingEnabled,
        bool $isLineItemsGroupingEnabled,
        bool $isShippingSelectionByLineItemEnabled
    ): void {
        $this->configProvider->expects(self::any())
            ->method('isMultiShippingEnabled')
            ->willReturn($isMultiShippingEnabled);
        $this->configProvider->expects(self::any())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn($isLineItemsGroupingEnabled);
        $this->configProvider->expects(self::any())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn($isShippingSelectionByLineItemEnabled);

        $context = new LayoutContext();
        $context->set('workflowName', 'another');
        $this->contextConfigurator->configureContext($context);

        self::assertSame($isShippingSelectionByLineItemEnabled, $context->get('multi_shipping_enabled'));
        self::assertSame($isLineItemsGroupingEnabled, $context->get('grouped_line_items_enabled'));
        self::assertNull($context->get('multi_shipping_type'));
    }

    /**
     * @dataProvider configureContextDataProvider
     */
    public function testConfigureContextForUnknownWorkflow(
        bool $isMultiShippingEnabled,
        bool $isLineItemsGroupingEnabled,
        bool $isShippingSelectionByLineItemEnabled
    ): void {
        $this->configProvider->expects(self::any())
            ->method('isMultiShippingEnabled')
            ->willReturn($isMultiShippingEnabled);
        $this->configProvider->expects(self::any())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn($isLineItemsGroupingEnabled);
        $this->configProvider->expects(self::any())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn($isShippingSelectionByLineItemEnabled);

        $context = new LayoutContext();
        $this->contextConfigurator->configureContext($context);

        self::assertSame($isShippingSelectionByLineItemEnabled, $context->get('multi_shipping_enabled'));
        self::assertSame($isLineItemsGroupingEnabled, $context->get('grouped_line_items_enabled'));
        self::assertNull($context->get('multi_shipping_type'));
    }

    public static function configureContextDataProvider(): array
    {
        return [
            [
                'isMultiShippingEnabled'               => false,
                'isLineItemsGroupingEnabled'           => false,
                'isShippingSelectionByLineItemEnabled' => false,
                'multi_shipping_type'                  => null
            ],
            [
                'isMultiShippingEnabled'               => true,
                'isLineItemsGroupingEnabled'           => true,
                'isShippingSelectionByLineItemEnabled' => false,
                'multi_shipping_type'                  => 'per_line_item_group'
            ],
            [
                'isMultiShippingEnabled'               => true,
                'isLineItemsGroupingEnabled'           => false,
                'isShippingSelectionByLineItemEnabled' => true,
                'multi_shipping_type'                  => 'per_line_item'
            ],
            [
                'isMultiShippingEnabled'               => true,
                'isLineItemsGroupingEnabled'           => true,
                'isShippingSelectionByLineItemEnabled' => true,
                'multi_shipping_type'                  => 'per_line_item'
            ]
        ];
    }
}
