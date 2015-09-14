<?php

namespace OroB2B\Bundle\CatalogBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler;

class ProductController extends Controller
{
    /**
     * @Route("/sidebar", name="orob2b_catalog_category_product_sidebar")
     * @Template
     *
     * @return array
     */
    public function sidebarAction()
    {
        /** @var RequestProductHandler $requestProductHandler */
        $requestProductHandler = $this->get('orob2b_catalog.handler.request_product');
        $defaultCategoryId = $requestProductHandler->getCategoryId();
        if (!$defaultCategoryId) {
            /** @var Category $rootCategory */
            $rootCategory = $this->getDoctrine()->getRepository('OroB2BCatalogBundle:Category')->getMasterCatalogRoot();
            $defaultCategoryId = $rootCategory->getId();
        }

        return ['defaultCategoryId' => $defaultCategoryId];
    }
}
