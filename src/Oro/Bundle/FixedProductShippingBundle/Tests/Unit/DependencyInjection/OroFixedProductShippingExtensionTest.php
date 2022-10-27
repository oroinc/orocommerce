<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FixedProductShippingBundle\DependencyInjection\OroFixedProductShippingExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroFixedProductShippingExtensionTest extends ExtensionTestCase
{
    protected OroFixedProductShippingExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new OroFixedProductShippingExtension();
    }

    public function testLoad(): void
    {
        $this->loadExtension($this->extension);

        $expectedDefinitions = [
            'oro_fixed_product_shipping.integration.channel',
            'oro_fixed_product_shipping.integration.transport',
            'oro_fixed_product_shipping.form.type.fixed_product_options',
            'oro_fixed_product_shipping.method.provider',
            'oro_fixed_product_shipping.disable_integration_listener',
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
