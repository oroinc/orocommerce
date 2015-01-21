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
            'orob2b_product.product.class',
            'orob2b_product.form.handler.product.classtest',
            'orob2b_product.product.manager.api.class',

        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'orob2b_product.form.product',
            'orob2b_product.form.handler.product',
            'orob2b_product.product.manager.api',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
