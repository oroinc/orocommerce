<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SaleBundle\DependencyInjection\OroSaleExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroSaleExtensionTest extends ExtensionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function buildContainerMock()
    {
        $mockBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(['setDefinition', 'setParameter', 'prependExtensionConfig', 'getParameter'])
            ->getMock();

        $mockBuilder
            ->expects($this->once())
            ->method('getParameter')
            ->with('kernel.bundles')
            ->willReturn(['OroShippingBundle' => []]);

        return $mockBuilder;
    }

    public function testLoad()
    {
        $this->loadExtension(new OroSaleExtension());

        $expectedParameters = [
            'oro_sale.entity.quote.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

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

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroSaleExtension();
        $this->assertEquals(OroSaleExtension::ALIAS, $extension->getAlias());
    }
}
