<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\OroWebCatalogExtension;

class OroPricingExtensionTest extends ExtensionTestCase
{
    public function testExtension()
    {
        $extension = new OroWebCatalogExtension();

        $this->loadExtension($extension);

        $expectedParameters = [
            'oro_web_catalog.entity.web_catalog.class',
            'oro_web_catalog.entity.content_node.class',
            'oro_web_catalog.entity.content_variant.class'
        ];

        $this->assertParametersLoaded($expectedParameters);
    }

    public function testGetAlias()
    {
        $extension = new OroWebCatalogExtension();

        $this->assertSame('oro_web_catalog', $extension->getAlias());
    }
}
