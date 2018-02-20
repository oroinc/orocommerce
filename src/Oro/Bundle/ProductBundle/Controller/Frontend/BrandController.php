<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class BrandController extends Controller
{
    const GRID_NAME = 'frontend-brand-search-grid';

    /**
     * View list of brands
     *
     * @Route("/", name="oro_product_frontend_brand_index")
     *
     * @return array
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * View list of products for brand
     *
     * @Route("/view/{id}", name="oro_product_frontend_brand_view", requirements={"id"="\d+"})
     *
     * @param Request $request
     * @param Brand $brand
     *
     * @return array
     */
    public function viewAction(Request $request, Brand $brand)
    {
        return [];
    }
}
