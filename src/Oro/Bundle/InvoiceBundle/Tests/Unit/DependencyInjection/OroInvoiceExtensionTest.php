<?php
namespace Oro\Bundle\InvoiceBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\InvoiceBundle\DependencyInjection\OroInvoiceExtension;

class OroInvoiceExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroInvoiceExtension());

        $expectedParameters = [
            'oro_invoice.entity.invoice.class'
        ];

        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'oro_invoice.form.type.invoice',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroInvoiceExtension();
        $this->assertEquals(OroInvoiceExtension::ALIAS, $extension->getAlias());
    }
}
