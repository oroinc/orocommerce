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
            'oro_pricing.api.update_lexemes_price_rule_processor',
            'oro_pricing.api.update_lexemes_on_price_rule_delete_processor',
            'oro_pricing.api.update_lexemes_on_price_rule_delete_list_processor',
            'oro_pricing.api.on_schedule_delete_processor',
            'oro_pricing.api.on_schedule_delete_list_processor',
            'oro_pricing.api_form_subscriber.add_schedules_to_price_list'
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
