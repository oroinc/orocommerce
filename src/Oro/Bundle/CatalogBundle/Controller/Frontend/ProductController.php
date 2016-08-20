<?php

namespace Oro\Bundle\CatalogBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\CatalogBundle\Controller\BaseProductController;

class ProductController extends BaseProductController
{
    /**
     * @Route("/sidebar", name="orob2b_catalog_frontend_category_product_sidebar")
     * @Template
     *
     * @return array
     */
    public function sidebarAction()
    {
        return parent::sidebarAction();
    }
}
