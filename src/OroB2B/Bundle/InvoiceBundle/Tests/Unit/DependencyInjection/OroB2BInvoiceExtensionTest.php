<?php
namespace OroB2B\Bundle\InvoiceBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\InvoiceBundle\DependencyInjection\OroB2BInvoiceExtension;

class OroB2BInvoiceExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroB2BInvoiceExtension());

        $expectedParameters = [
            'orob2b_invoice.entity.invoice.class'
        ];

        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'orob2b_invoice.form.type.invoice',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroB2BInvoiceExtension();
        $this->assertEquals(OroB2BInvoiceExtension::ALIAS, $extension->getAlias());
    }
}
