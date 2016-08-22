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
            'orob2b_product.entity.product.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'orob2b_product.form.type.product',
            'orob2b_product.service.quantity_rounding',
            'orob2b_product.form.type.product_step_one',
            'orob2b_product.service.product_create_step_one_handler',
            'orob2b_product.provider.default_product_unit_provider.chain',
            'orob2b_product.provider.default_product_unit_provider.system',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $expectedExtensionConfigs = [
            'orob2b_product',
        ];
        $this->assertExtensionConfigsLoaded($expectedExtensionConfigs);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroProductExtension();
        $this->assertEquals('orob2b_product', $extension->getAlias());
    }
}
