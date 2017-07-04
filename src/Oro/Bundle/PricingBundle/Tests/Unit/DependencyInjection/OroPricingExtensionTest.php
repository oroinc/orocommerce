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
            'oro_pricing.api.rebuild_price_lists_for_website_customer_group',
            'oro_pricing.api.rebuild_price_lists_for_website_customer',
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
