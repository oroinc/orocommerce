<?php
namespace Oro\Bundle\OrderBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\OrderBundle\DependencyInjection\OroOrderExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroOrderExtensionTest extends ExtensionTestCase
{
    /**
     * @var array
     */
    protected $extensionConfigs = [];

    public function testLoad()
    {
        $this->loadExtension(new OroOrderExtension());

        $expectedParameters = [
            'oro_order.entity.order.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'oro_order.form.type.order',
            'oro_order.form.type.order_shipping_tracking',
            'oro_order.form.type.order_shipping_tracking_collection',
            'oro_order.form.type.select_switch_input',
            'oro_order.handler.order_shipping_tracking',
            'oro_order.twig.order_shipping',
            'oro_order.formatter.shipping_tracking',
            'oro_order.factory.shipping_context',
            'oro_order.event_listener.order.possible_shipping_methods',
            'oro_order.converter.shipping_prices',
            'oro_order.order_api.product_cacher_processor',
            'oro_order.product_provider.sku_cached',
            'oro_order.order_api.totals_processor',
            'oro_order.order_api.form_builder_default_website',
            'oro_order.order_api.form_builder_totals_processor',
            'oro_order.order_api.line_item_product_processor',
            'oro_order.order_api.order_line_item_price_processor',
            'oro_order.form_event_subscriber.default_website',
            'oro_order.form_event_subscriber.discount'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded([OroOrderExtension::ALIAS]);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroOrderExtension();
        static::assertEquals(OroOrderExtension::ALIAS, $extension->getAlias());
    }
}
