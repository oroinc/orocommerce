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
            'oro_commerce_menu.menu.condition.condition_extension',
            'oro_commerce_menu.menu.condition.config_value_expression_language_provider',
            'oro_commerce_menu.menu.condition.logged_in_expression_language_provider',
            'oro_commerce_menu.twig.menu_extension',
            'oro_commerce_menu.ownership_provider.global',
            'oro_commerce_menu.ownership_provider.account',
            'oro_commerce_menu.namespace_migration_provider',
            'oro_commerce_menu.data_provider.menu',
        ];
        $this->assertDefinitionsLoaded($expectedServices);
    }
}
