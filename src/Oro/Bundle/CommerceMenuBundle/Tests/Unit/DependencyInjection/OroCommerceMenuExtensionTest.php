<?php

namespace Oro\Bundle\CommerceMenuBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\CommerceMenuBundle\DependencyInjection\OroCommerceMenuExtension;

class OroCommerceMenuExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroCommerceMenuExtension());

        $expectedServices = [
            'oro_commerce_menu.ownership_provider.account',
        ];
        $this->assertDefinitionsLoaded($expectedServices);
    }
}
