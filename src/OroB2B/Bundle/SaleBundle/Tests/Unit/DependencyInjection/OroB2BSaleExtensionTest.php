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
            'orob2b_sale.quote.class',
            'orob2b_sale.form.type.quote.class',
            'orob2b_sale.form.handler.quote.class',
            'orob2b_sale.quote.manager.api.class',
            'orob2b_sale.doctrine.subscriber.entity.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'orob2b_sale.form.quote',
            'orob2b_sale.form.type.quote',
            'orob2b_sale.form.handler.quote',
            'orob2b_sale.quote.manager.api',
            'orob2b_sale.doctrine.subscriber.entity',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
