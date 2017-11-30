<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TaxBundle\DependencyInjection\OroTaxExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroTaxExtensionTest extends ExtensionTestCase
{
    public function testExtension()
    {
        $extension = new OroTaxExtension();

        $this->loadExtension($extension);

        $expectedDefinitions = [
            'oro_tax.importexport.configuration_provider.tax_rule',
            'oro_tax.importexport.configuration_provider.tax',
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    public function testGetAlias()
    {
        $extension = new OroTaxExtension();

        $this->assertSame('oro_tax', $extension->getAlias());
    }
}
