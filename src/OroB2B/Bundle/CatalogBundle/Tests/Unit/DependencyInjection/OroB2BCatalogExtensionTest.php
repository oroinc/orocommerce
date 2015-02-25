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
            'orob2b_catalog.category.class',
        ];
        $this->assertParametersLoaded($expectedParameters);
    }
}
