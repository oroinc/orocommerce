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
            'oro_order.form.type.event_listener.subtotals_subscriber',
            'oro_order.api.form_listener.discount',
            'oro_order.api.handle_order_included_data',
            'oro_order.api.update_request_data_for_order_line_item',
            'oro_order.api.set_price_by_value_and_currency',
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
