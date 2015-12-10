<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;

class OroB2BPricingExtensionTest extends ExtensionTestCase
{
    public function testExtension()
    {
        $extension = new OroB2BPricingExtension();

        $this->loadExtension($extension);

        $expectedParameters = [
            'orob2b_pricing.entity.price_list.class',
            'orob2b_pricing.entity.price_list_currency.class'
        ];

        $this->assertParametersLoaded($expectedParameters);

        $this->assertEquals('oro_b2b_pricing', $extension->getAlias());
    }

    public function testGetAlias()
    {
        $extension = new OroB2BPricingExtension();

        $this->assertSame('oro_b2b_pricing', $extension->getAlias());
    }
}
