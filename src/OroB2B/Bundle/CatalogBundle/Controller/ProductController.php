<?php

namespace OroB2B\Bundle\CatalogBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class ProductController extends BaseProductController
{
    /**
     * @Route("/sidebar", name="orob2b_catalog_category_product_sidebar")
     * @Template
     *
     * @return array
     */
    public function sidebarAction()
    {
        return parent::sidebarAction();
    }
}
