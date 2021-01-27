<?php
declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ShoppingListBundle\DependencyInjection\OroShoppingListExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroShoppingListExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroShoppingListExtension());

        $expectedDefinitions = [
            // Services
            'oro_shopping_list.validator.line_item',
            'oro_shopping_list.line_item.manager.api',
            'oro_shopping_list.shopping_list.manager.api',
            'oro_shopping_list.manager.shopping_list',
            'oro_shopping_list.placeholder.filter',
            'oro_shopping_list.condition.rfp_allowed',
            'oro_shopping_list.provider.matrix_grid_order_manager',
            'oro_shopping_list.line_item.factory.configurable_product',
            'oro_shopping_list.entity_listener.line_item.remove_parent_products_from_shopping_list',
            'oro_shopping_list.manager.empty_matrix_grid',

            // Forms
            'oro_shopping_list.form.type.shopping_list',
            'oro_shopping_list.form.type.line_item',
            'oro_shopping_list.form.type.frontend_line_item_widget',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded([OroShoppingListExtension::ALIAS]);
    }

    public function testGetAlias(): void
    {
        static::assertEquals(OroShoppingListExtension::ALIAS, (new OroShoppingListExtension())->getAlias());
    }
}
