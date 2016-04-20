<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\ShoppingListBundle\DependencyInjection\OroB2BShoppingListExtension;

class OroB2BShoppingListExtensionTest extends ExtensionTestCase
{
    /**
     * @var array
     */
    protected $extensionConfigs = [];

    public function testLoad()
    {
        $this->loadExtension(new OroB2BShoppingListExtension());

        $expectedParameters = [
            // Entity
            'orob2b_shopping_list.entity.shopping_list.class',
            'orob2b_shopping_list.entity.line_item.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            // Services
            'orob2b_shopping_list.validator.line_item',
            'orob2b_shopping_list.line_item.manager.api',
            'orob2b_shopping_list.shopping_list.manager.api',
            'orob2b_shopping_list.shopping_list.manager',
            'orob2b_shopping_list.placeholder.filter',

            // Listeners
            'orob2b_shopping_list.event_listener.shopping_list_listener',

            // Forms
            'orob2b_shopping_list.form.type.shopping_list',
            'orob2b_shopping_list.form.type.line_item',
            'orob2b_shopping_list.form.type.frontend_line_item_widget',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded([OroB2BShoppingListExtension::ALIAS]);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroB2BShoppingListExtension();
        $this->assertEquals(OroB2BShoppingListExtension::ALIAS, $extension->getAlias());
    }
}
