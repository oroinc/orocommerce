<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Autocomlete;

use OroB2B\Bundle\ProductBundle\Tests\Functional\Autocomplete\AbstractProductVisibilityLimitedSearchHandlerTest;

/**
 * @dbIsolation
 */
class ProductVisibilityLimitedSearchHandlerTest extends AbstractProductVisibilityLimitedSearchHandlerTest
{
    /** @var string  */
    protected $scope = 'quote';

    /** @var string  */
    protected $configPath = 'oro_b2b_sale.product_visibility.value';
}
