<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Autocomlete;

use OroB2B\Bundle\ProductBundle\Tests\Functional\Autocomplete\AbstractProductVisibilityLimitedSearchHandlerTest;

/**
 * @dbIsolation
 */
class ProductVisibilityLimitedSearchHandlerTest extends AbstractProductVisibilityLimitedSearchHandlerTest
{
    /** @var string  */
    protected $scope = 'rfp';

    /** @var string  */
    protected $configPath = 'oro_b2b_rfp.product_visibility.value';
}
