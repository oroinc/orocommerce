<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\FrontendNavigationBundle\DependencyInjection\OroFrontendNavigationExtension;

class OroFrontendNavigationExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroFrontendNavigationExtension());

        $expectedServices = [
            'oro_frontend_navigation.menu_update_provider.frontend',
        ];
        $this->assertDefinitionsLoaded($expectedServices);
    }
}
