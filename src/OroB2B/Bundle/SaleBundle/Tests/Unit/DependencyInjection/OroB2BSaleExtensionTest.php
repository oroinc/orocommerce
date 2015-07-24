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
            'orob2b_sale.quote.manager.api.class',
            'orob2b_sale.event_listener.entity_subscriber.class',
            // validators
            'orob2b_sale.validator.quote_product_unit.class',
            // form types
            'orob2b_sale.form.type.quote_product.class',
            'orob2b_sale.form.type.quote_product_offer.class',
            'orob2b_sale.form.type.quote_product_collection.class',
            'orob2b_sale.form.type.quote_product_offer_collection.class',
            // twig extensions
            'orob2b_sale.twig.quote.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'orob2b_sale.quote.manager.api',
            'orob2b_sale.event_listener.entity_subscriber',
            // validators
            'orob2b_sale.validator.quote_product_unit',
            // form types
            'orob2b_sale.form.type.quote_product',
            'orob2b_sale.form.type.quote_product_offer',
            'orob2b_sale.form.type.quote_product_collection',
            'orob2b_sale.form.type.quote_product_offer_collection',
            // twig extensions
            'orob2b_sale.twig.quote',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
