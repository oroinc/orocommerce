<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\ProductBundle\DependencyInjection\OroProductExtension;

class OroProductExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroProductExtension());

        $expectedParameters = [
            'oro_product.entity.product.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'oro_product.form.type.product',
            'oro_product.form.type.product_step_one',
            'oro_product.service.product_create_step_one_handler',
            'oro_product.provider.default_product_unit_provider.chain',
            'oro_product.provider.default_product_unit_provider.system',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $expectedExtensionConfigs = [
            'oro_product',
        ];
        $this->assertExtensionConfigsLoaded($expectedExtensionConfigs);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroProductExtension();
        $this->assertEquals('oro_product', $extension->getAlias());
    }
}
