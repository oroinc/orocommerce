<?php

namespace OroB2B\Bundle\CatalogBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

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
