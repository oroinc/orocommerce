<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\ProductBundle\DependencyInjection\OroB2BProductExtension;

class OroB2BProductExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroB2BProductExtension());

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
        $extension = new OroB2BProductExtension();
        $this->assertEquals('orob2b_product', $extension->getAlias());
    }
}
