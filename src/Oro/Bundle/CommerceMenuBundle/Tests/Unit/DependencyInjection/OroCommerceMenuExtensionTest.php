<?php

namespace Oro\Bundle\CommerceMenuBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CommerceMenuBundle\DependencyInjection\OroCommerceMenuExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroCommerceMenuExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroCommerceMenuExtension());

        $expectedServices = [
            'oro_commerce_menu.manager.menu_update',
        ];
        $this->assertDefinitionsLoaded($expectedServices);
    }
}
