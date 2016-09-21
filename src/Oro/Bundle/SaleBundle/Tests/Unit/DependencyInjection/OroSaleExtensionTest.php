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
            'oro_sale.entity.quote.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            // validators
            'oro_sale.validator.quote_product',
            // form types
            'oro_sale.form.type.quote_product',
            'oro_sale.form.type.quote_product_offer',
            'oro_sale.form.type.quote_product_collection',
            'oro_sale.form.type.quote_product_offer_collection',
            // twig extensions
            'oro_sale.twig.quote',
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
