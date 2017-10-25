<?php

namespace Oro\Bundle\CatalogBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class ProductController extends Controller
{
    /**
     * View List of ALL products
     *
     * @Route("/allproducts", name="oro_catalog_frontend_product_allproducts")
     * @Layout(vars={"entity_class", "grid_config", "theme_name"})
     * @AclAncestor("oro_product_frontend_view")
     *
     * @return array
     */
    public function allProductsAction()
    {
        // TODO for BB-12602, check added in BB-12410 changes in this file, maybe during layout refactoring
        // this also should be adjusted
        return [
            'entity_class' => $this->container->getParameter('oro_product.entity.product.class'),
            'grid_config' => [
                'frontend-catalog-allproducts-grid'
            ],
            'theme_name' => $this->container
                ->get('oro_product.datagrid_theme_helper')
                ->getTheme('frontend-product-search-grid')
        ];
    }
}
