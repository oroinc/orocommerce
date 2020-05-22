<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ShippingBundle\DependencyInjection\OroShippingExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroShippingExtensionTest extends ExtensionTestCase
{
    /**
     * @var OroShippingExtension
     */
    protected $extension;

    protected function setUp(): void
    {
        $this->extension = new OroShippingExtension();
    }

    protected function tearDown(): void
    {
        unset($this->extension);
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedDefinitions = [
            'oro_shipping.form.type.shipping_origin',
            'oro_shipping.form.type.shipping_origin_config',
            'oro_shipping.form.type.length_unit_select',
            'oro_shipping.form.type.weight_unit_select',
            'oro_shipping.form.type.freight_class_select',
            'oro_shipping.form.type.weight',
            'oro_shipping.form.type.shipping_methods_configs_rule',
            'oro_shipping.form.type.shipping_method_type_config_collection',
            'oro_shipping.form.type.shipping_methods_configs_rule_destination',
            'oro_shipping.form.type.shipping_method_config',
            'oro_shipping.form.listener.rule_destination',
            'oro_shipping.form_event_subscriber.method_type_config_collection_subscriber',
            'oro_shipping.form_event_subscriber.method_config_collection_subscriber',
            'oro_shipping.form_event_subscriber.method_config_subscriber',
            'oro_shipping.form.type.dimensions',
            'oro_shipping.form.type.dimensions_value',
            'oro_shipping.form.product_shipping_option',
            'oro_shipping.form.product_shipping_option_collection',
            'oro_shipping.form.extension.product_type',
            'oro_shipping.form.type.shipping_method_config_collection',
            'oro_shipping.mass_action.status.enable',
            'oro_shipping.mass_action.status.disable',
            'oro_shipping.mass_action.status_handler',
            'oro_shipping.factory.shipping_origin_model_factory',
            'oro_shipping.event_listener.config.shipping_origin',
            'oro_shipping.shipping_method_provider',
            'oro_shipping.formatter.shipping_method_label',
            'oro_shipping.twig.shipping_method_extension',
            'oro_shipping.shipping_price.provider',
            'oro_shipping.shipping_price.provider_enabled_methods_decorator',
            'oro_shipping.provider.measure_units.conversion',
            'oro_shipping.condition.has_applicable_shipping_methods',
            'oro_shipping.condition.shipping_method_has_shipping_rules',
            'oro_shipping.method.view_factory',
            'oro_shipping.method.composed_configuration_builder_factory',
            'oro_shipping.datagrid.shipping_rule_actions_visibility_provider',
            'oro_shipping.converter.shipping_context_to_rule_values',
            'oro_shipping.listener.shipping_rule',
            'oro_shipping.expression_language.decorated_product_line_item_factory',
            'oro_shipping.listener.shipping_rule',
            'oro_shipping.layout.block_type.shipping_methods',
            'oro_shipping.helper.filtered_datagrid_route',
            'oro_shipping.helper.filtered_datagrid_route',
            'oro_shipping.validator.shipping_rule_enabled',
            'oro_shipping.checker.shipping_method_enabled',
            'oro_shipping.checker.shipping_rule_enabled',
            'oro_shipping.method_disable_handler.decorator',
            'oro_shipping.method_disable_handler.basic',
            'oro_shipping.repository.shipping_method_config',
            'oro_shipping.method.event_listener.method_renaming',
            'oro_shipping.method.event.dispatcher.method_renaming',
            'oro_shipping.repository.shipping_method_type_config',
            'oro_shipping.method_validator.decorator.basic_enabled_shipping_methods_by_rules',
            'oro_shipping.factory.shipping_package_options',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded([OroShippingExtension::ALIAS]);
    }

    public function testGetAlias()
    {
        $this->assertEquals(OroShippingExtension::ALIAS, $this->extension->getAlias());
    }
}
