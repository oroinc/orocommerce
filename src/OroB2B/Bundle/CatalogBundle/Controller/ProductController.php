<?php

namespace OroB2B\Bundle\CatalogBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends Controller
{
    /**
     * @Route("/sidebar", name="orob2b_catalog_category_product_sidebar")
     * @Template
     *
     * @param Request $request
     * @return array
     */
    public function sidebarAction(Request $request)
    {
        if ($request->query->has('categoryId')) {
            $defaultCategoryId = $request->query->get('categoryId');
        } else {
            $defaultCategoryId = $this->getMasterRootCategory()->getId();
        }

        return ['defaultCategoryId' => $defaultCategoryId];
    }

    /**
     * @return Category
     */
    protected function getMasterRootCategory()
    {
        return $this->getDoctrine()->getRepository('OroB2BCatalogBundle:Category')->getMasterCatalogRoot();
    }
}
