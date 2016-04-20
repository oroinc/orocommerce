<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use OroB2B\Bundle\SaleBundle\DependencyInjection\OroB2BSaleExtension;

class OroB2BSaleExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroB2BSaleExtension());

        $expectedParameters = [
            'orob2b_sale.entity.quote.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            // validators
            'orob2b_sale.validator.quote_product',
            // form types
            'orob2b_sale.form.type.quote_product',
            'orob2b_sale.form.type.quote_product_offer',
            'orob2b_sale.form.type.quote_product_collection',
            'orob2b_sale.form.type.quote_product_offer_collection',
            // twig extensions
            'orob2b_sale.twig.quote',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded([OroB2BSaleExtension::ALIAS]);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroB2BSaleExtension();
        $this->assertEquals(OroB2BSaleExtension::ALIAS, $extension->getAlias());
    }
}
