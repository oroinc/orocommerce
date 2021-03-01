<?php
declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SaleBundle\DependencyInjection\OroSaleExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroSaleExtensionTest extends ExtensionTestCase
{
    protected function buildContainerMock(): ContainerBuilder
    {
        $containerBuilder = parent::buildContainerMock();

        $containerBuilder
            ->expects(static::once())
            ->method('getParameter')
            ->with('kernel.bundles')
            ->willReturn(['OroShippingBundle' => []]);

        return $containerBuilder;
    }

    public function testLoad(): void
    {
        $this->loadExtension(new OroSaleExtension());

        $expectedDefinitions = [
            // validators
            'oro_sale.validator.quote_product',
            // form types
            'oro_sale.form.type.quote_product',
            'oro_sale.form.type.quote_product_offer',
            'oro_sale.form.type.quote_product_collection',
            'oro_sale.form.type.quote_product_offer_collection',
            // twig extensions
            'oro_sale.twig.quote',
            // event listeners
            'oro_sale.quote_event_listener.possible_shipping_methods',
            // services
            'oro_sale.quote_demand.subtotals_calculator_main',
            'oro_sale.quote.selected_offers_shipping_context_factory',
            'oro_sale.quote.first_offers_shipping_context_factory',
            'oro_sale.quote.shipping_configuration_factory',
            'oro_sale.quote.configured_shipping_price_provider',
            'oro_sale.quote.configured_shipping_price_provider_overridden_decorator',
            'oro_sale.quote.configured_shipping_price_provider_allow_unlisted_decorator',
            'oro_sale.quote.configured_shipping_price_provider_method_locked_decorator',
            'oro_sale.quote_demand.subtotals_calculator_shipping_cost_decorator',
            'oro_sale.quote.shipping_line_items_converter_first_offers',
            'oro_sale.quote.shipping_line_items_converter_selected_offers',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded([OroSaleExtension::ALIAS]);
    }

    public function testGetAlias(): void
    {
        static::assertEquals(OroSaleExtension::ALIAS, (new OroSaleExtension())->getAlias());
    }
}
