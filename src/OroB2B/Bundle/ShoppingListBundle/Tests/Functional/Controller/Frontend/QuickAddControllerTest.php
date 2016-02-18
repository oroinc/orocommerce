<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use OroB2B\Bundle\ProductBundle\Tests\Functional\Controller\Frontend\QuickAddControllerTest as BaseControllerTest;

/**
 * @dbIsolation
 */
class QuickAddControllerTest extends BaseControllerTest
{
    /**
     * @return array
     */
    public function validationResultProvider()
    {
        return [
            'add to shopping list' => [
                'processorName' => 'orob2b_shopping_list_quick_add_processor',
                'routerName' => 'orob2b_product_frontend_quick_add',
                'routerParams' => [],
                'expectedMessage' => '3 products were added'
            ],
        ];
    }
}
