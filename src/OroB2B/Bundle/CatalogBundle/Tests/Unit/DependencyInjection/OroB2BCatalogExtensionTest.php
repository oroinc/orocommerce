<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\CatalogBundle\DependencyInjection\OroB2BCatalogExtension;

class OroB2BCatalogExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroB2BCatalogExtension());

        $expectedParameters = [
            'orob2b_catalog.entity.category.class',
        ];
        $expectedServices = [
            'orob2b_catalog.form.extension.product_type',
            'orob2b_catalog.form.extension.product_step_one_type'
        ];
        $this->assertParametersLoaded($expectedParameters);
        $this->assertDefinitionsLoaded($expectedServices);
    }
}
