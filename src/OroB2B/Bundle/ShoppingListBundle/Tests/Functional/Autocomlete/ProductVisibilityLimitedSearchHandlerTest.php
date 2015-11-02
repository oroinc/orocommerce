<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Autocomlete;

use OroB2B\Bundle\ProductBundle\Tests\Functional\Autocomplete\AbstractProductVisibilityLimitedSearchHandlerTest;

/**
 * @dbIsolation
 */
class ProductVisibilityLimitedSearchHandlerTest extends AbstractProductVisibilityLimitedSearchHandlerTest
{
    /** @var string  */
    protected $scope = 'shopping_list';

    /** @var string  */
    protected $configPath = 'oro_b2b_shopping_list.product_visibility.value';
}
