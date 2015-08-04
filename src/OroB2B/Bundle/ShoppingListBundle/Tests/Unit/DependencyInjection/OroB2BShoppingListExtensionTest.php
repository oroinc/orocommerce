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
            // Validators
            'orob2b_shopping_list.validator.line_item.class',

            // Entity
            'orob2b_shopping_list.entity.shopping_list.class',
            'orob2b_shopping_list.entity.line_item.class',

            // Managers
            'orob2b_shopping_list.shopping_list.manager.api.class',
            'orob2b_shopping_list.shopping_list.manager.class',
            'orob2b_shopping_list.line_item.manager.class',

            // Form types
            'orob2b_shopping_list.form.type.shopping_list.class',
            'orob2b_shopping_list.form.type.line_item.class',
            'orob2b_shopping_list.form.type.frontend_line_item_widget.class',
            'orob2b_shopping_list.form.type.frontend_line_item.class',

            // Event listeners
            'orob2b_shopping_list.event_listener.datagrid.class',
            'orob2b_shopping_list.event_listener.form_view.class',
            'orob2b_shopping_list.event_listener.shopping_list_listener.class',
            'orob2b_shopping_list.event_listener.form.type.line_item_subscriber.class'
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            // Services
            'orob2b_shopping_list.validator.line_item',
            'orob2b_shopping_list.line_item.manager',
            'orob2b_shopping_list.line_item.manager.api',
            'orob2b_shopping_list.shopping_list.manager.api',
            'orob2b_shopping_list.shopping_list.manager',

            // Listeners
            'orob2b_shopping_list.event_listener.datagrid',
            'orob2b_shopping_list.event_listener.shopping_list_listener',
            'orob2b_shopping_list.event_listener.form_view',

            // Forms
            'orob2b_shopping_list.form.type.shopping_list',
            'orob2b_shopping_list.form.type.line_item',
            'orob2b_shopping_list.form.type.frontend_line_item',
            'orob2b_shopping_list.form.type.frontend_line_item_widget',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    /**
     * {@inheritDoc}
     */
    protected function buildContainerMock()
    {
        return $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(['setDefinition', 'setParameter'])
            ->getMock();
    }
}
