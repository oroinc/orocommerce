<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CatalogBundle\DependencyInjection\OroCatalogExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroCatalogExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroCatalogExtension());

        $expectedServices = [
            'oro_catalog.form.extension.product_type',
            'oro_catalog.form.extension.product_step_one_type',
            'oro_catalog.provider.default_product_unit_provider.category'
        ];
        $this->assertDefinitionsLoaded($expectedServices);

        $expectedExtensionConfigs = [
            'oro_catalog',
        ];
        $this->assertExtensionConfigsLoaded($expectedExtensionConfigs);
    }
}
