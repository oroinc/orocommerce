<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\PricingBundle\DependencyInjection\OroPricingExtension;

class OroPricingExtensionTest extends ExtensionTestCase
{
    public function testExtension()
    {
        $extension = new OroPricingExtension();

        $this->loadExtension($extension);

        $expectedParameters = [
            'oro_pricing.entity.price_list.class',
            'oro_pricing.entity.price_list_currency.class'
        ];

        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'oro_pricing.api.handle_price_list_status_change',
            'oro_pricing.api.update_price_list_lexemes',
            'oro_pricing.api.update_price_list_contains_schedule_on_schedule_delete',
            'oro_pricing.api.update_price_list_contains_schedule_on_schedule_delete_list',
            'oro_pricing.api.build_combined_price_list_on_schedule_save',
            'oro_pricing.api.build_combined_price_list_on_schedule_delete',
            'oro_pricing.api.build_combined_price_list_on_schedule_delete_list',
            'oro_pricing.api.update_lexemes_price_rule',
            'oro_pricing.api.update_lexemes_on_price_rule_delete',
            'oro_pricing.api.update_lexemes_on_price_rule_delete_list',
            'oro_pricing.api.on_schedule_delete',
            'oro_pricing.api.on_schedule_delete_list',
            'oro_pricing.api_form_subscriber.add_schedules_to_price_list',
            'oro_pricing.api.set_price_by_value_and_currency',
            'oro_pricing.api.normalize_product_price_id',
            'oro_pricing.api.price_list_id_in_context_storage',
            'oro_pricing.api.product_price_id_by_price_list_id_normalizer',
            'oro_pricing.api.normalize_product_price',
            'oro_pricing.api.set_price_list_in_context_by_product_price',
            'oro_pricing.update.load_normalized_entity',
            'oro_pricing.api.set_price_list_in_context_by_filter',
            'oro_pricing.api.enabled_price_sharding_on_query',
            'oro_pricing.api.enable_price_sharding_on_config',
            'oro_pricing.api.save_product_price',
            'oro_pricing.api.price_manager_delete_handler',
            'oro_pricing.api.reset_price_rule_field_on_update',
            'oro_pricing.api.price_list_relation_trigger_handler_for_website_and_customer_group',
            'oro_pricing.api.price_list_relation_trigger_handler_for_website_and_customer',
            'oro_pricing.importexport.normalizer.product_price',
            'oro_pricing.importexport.data_converter.price_attribute_product_price',
            'oro_pricing.importexport.normalizer.price_attribute_product_price',
            'oro_pricing.importexport.configuration_provider.price_attribute_product_price',
            'oro_pricing.importexport.template_fixture.price_attribute_product_price',
            'oro_pricing.importexport.processor.export.price_attribute_product_price',
            'oro_pricing.importexport.processor.export_template.price_attribute_product_price',
            'oro_pricing.importexport.strategy.price_attribute_product_price.add_or_replace',
            'oro_pricing.importexport.processor.import.price_attribute_product_price',
            'oro_pricing.importexport.writer.price_attribute_product_price',
            'oro_pricing.importexport.strategy.price_attribute_product_price.reset',
            'oro_pricing.importexport.processor.import.price_attribute_product_price.reset',
            'oro_pricing.importexport.configuration_provider.product_price',
            'oro_pricing.entity_listener.remove_price_list_from_configuration',
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertEquals('oro_pricing', $extension->getAlias());
    }

    public function testGetAlias()
    {
        $extension = new OroPricingExtension();

        $this->assertSame('oro_pricing', $extension->getAlias());
    }
}
