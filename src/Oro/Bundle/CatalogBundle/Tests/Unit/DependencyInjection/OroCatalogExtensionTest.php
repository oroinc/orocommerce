<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\CatalogBundle\DependencyInjection\OroCatalogExtension;

class OroCatalogExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroCatalogExtension());

        $expectedParameters = [
            'orob2b_catalog.entity.category.class',
        ];
        $expectedServices = [
            'orob2b_catalog.form.extension.product_type',
            'orob2b_catalog.form.extension.product_step_one_type',
            'orob2b_catalog.provider.default_product_unit_provider.category'
        ];
        $this->assertParametersLoaded($expectedParameters);
        $this->assertDefinitionsLoaded($expectedServices);
    }
}
