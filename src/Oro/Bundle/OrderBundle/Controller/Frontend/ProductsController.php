<?php

namespace Oro\Bundle\OrderBundle\Controller\Frontend;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\OrderBundle\Controller\AbstractOrderController;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\Routing\Annotation\Route;

class ProductsController extends AbstractOrderController
{
    const PRODUCT_GRID_NAME = 'order-products-previously-purchased-grid';

    /**
     * @Route("/previously-purchased", name="oro_order_products_frontend_previously_purchased")
     * @AclAncestor("oro_order_frontend_view")
     * @Layout(vars={"entity_class", "product_grid_name", "grid_config", "theme_name"})
     *
     * @return array
     */
    public function previouslyPurchasedAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_product.entity.product.class'),
            'product_grid_name' => self::PRODUCT_GRID_NAME,
            'grid_config' => [
                self::PRODUCT_GRID_NAME
            ],
            'theme_name' => $this->container
                ->get('oro_product.datagrid_theme_helper')
                ->getTheme('order-products-previously-purchased-grid')
        ];
    }
}
