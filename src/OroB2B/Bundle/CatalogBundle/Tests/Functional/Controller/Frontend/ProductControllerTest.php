<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Functional\Controller\Frontend;

use OroB2B\Bundle\CatalogBundle\Tests\Functional\Controller\ProductControllerTest as BaseTest;

/**
 * @dbIsolation
 */
class ProductControllerTest extends BaseTest
{
    /**
     * @var string
     */
    protected $gridName = 'frontend-products-grid';

    /**
     * @var string
     */
    protected $route = 'orob2b_catalog_frontend_category_product_sidebar';


}
