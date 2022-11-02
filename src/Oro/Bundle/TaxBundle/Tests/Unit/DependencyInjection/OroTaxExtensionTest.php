<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TaxBundle\Controller\Api\Rest\ProductTaxCodeController;
use Oro\Bundle\TaxBundle\DependencyInjection\OroTaxExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroTaxExtensionTest extends ExtensionTestCase
{
    public function testExtension(): void
    {
        $extension = new OroTaxExtension();

        $this->loadExtension($extension);

        $expectedDefinitions = [
            'oro_tax.importexport.configuration_provider.tax_rule',
            'oro_tax.importexport.configuration_provider.tax',
            ProductTaxCodeController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
