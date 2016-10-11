<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\ShoppingListBundle\DependencyInjection\OroShoppingListExtension;

class OroShoppingListExtensionTest extends ExtensionTestCase
{
    /**
     * @var array
     */
    protected $extensionConfigs = [];

    public function testLoad()
    {
        $this->loadExtension(new OroShoppingListExtension());

        $expectedParameters = [
            // Entity
            'oro_shopping_list.entity.shopping_list.class',
            'oro_shopping_list.entity.line_item.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            // Services
            'oro_shopping_list.validator.line_item',
            'oro_shopping_list.line_item.manager.api',
            'oro_shopping_list.shopping_list.manager.api',
            'oro_shopping_list.shopping_list.manager',
            'oro_shopping_list.placeholder.filter',

            // Listeners
            'oro_shopping_list.event_listener.shopping_list_listener',

            // Forms
            'oro_shopping_list.form.type.shopping_list',
            'oro_shopping_list.form.type.line_item',
            'oro_shopping_list.form.type.frontend_line_item_widget',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded([OroShoppingListExtension::ALIAS]);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroShoppingListExtension();
        $this->assertEquals(OroShoppingListExtension::ALIAS, $extension->getAlias());
    }
}
