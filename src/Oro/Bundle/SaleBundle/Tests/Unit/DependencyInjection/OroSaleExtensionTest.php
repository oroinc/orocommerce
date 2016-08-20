<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\SaleBundle\DependencyInjection\OroSaleExtension;

class OroSaleExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroSaleExtension());

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

        $this->assertExtensionConfigsLoaded([OroSaleExtension::ALIAS]);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroSaleExtension();
        $this->assertEquals(OroSaleExtension::ALIAS, $extension->getAlias());
    }
}
