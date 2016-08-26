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
            'orob2b_pricing.entity.price_list.class',
            'orob2b_pricing.entity.price_list_currency.class'
        ];

        $this->assertParametersLoaded($expectedParameters);

        $this->assertEquals('oro_b2b_pricing', $extension->getAlias());
    }

    public function testGetAlias()
    {
        $extension = new OroPricingExtension();

        $this->assertSame('oro_b2b_pricing', $extension->getAlias());
    }
}
