<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Autocomlete;

use OroB2B\Bundle\ProductBundle\Tests\Functional\Autocomplete\AbstractProductVisibilityLimitedSearchHandlerTest;

/**
 * @dbIsolation
 */
class ProductVisibilityLimitedSearchHandlerTest extends AbstractProductVisibilityLimitedSearchHandlerTest
{
    /** @var string  */
    protected $scope = 'order';

    /** @var string  */
    protected $configPath = 'oro_b2b_order.product_visibility.value';
}
