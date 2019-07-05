<?php

namespace Oro\Bundle\OrderBundle\Controller\Frontend;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Show previously purchased products for customer
 */
class ProductsController extends AbstractController
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
            'entity_class' => Product::class,
            'product_grid_name' => self::PRODUCT_GRID_NAME,
            'grid_config' => [
                self::PRODUCT_GRID_NAME
            ],
            'theme_name' => $this->get(DataGridThemeHelper::class)
                ->getTheme('order-products-previously-purchased-grid')
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            DataGridThemeHelper::class,
        ]);
    }
}
