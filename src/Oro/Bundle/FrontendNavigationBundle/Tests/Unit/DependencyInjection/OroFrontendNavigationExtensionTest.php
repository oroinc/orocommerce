<?php

namespace Oro\Bundle\FrontendNavigationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\FrontendNavigationBundle\DependencyInjection\OroFrontendNavigationExtension;

class OroFrontendNavigationExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroFrontendNavigationExtension());

        $expectedServices = [
            'oro_frontend_navigation.ownership_provider.account',
        ];
        $this->assertDefinitionsLoaded($expectedServices);
    }
}
