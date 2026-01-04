<?php

namespace Oro\Bundle\OrderBundle\Controller\Frontend;

use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Show previously purchased products for customer
 */
class ProductsController extends AbstractController
{
    public const PRODUCT_GRID_NAME = 'order-products-previously-purchased-grid';

    /**
     * @return array
     */
    #[Route(path: '/previously-purchased', name: 'oro_order_products_frontend_previously_purchased')]
    #[Layout(vars: ['entity_class', 'product_grid_name', 'grid_config', 'theme_name'])]
    #[AclAncestor('oro_order_frontend_view')]
    public function previouslyPurchasedAction()
    {
        return [
            'entity_class' => Product::class,
            'product_grid_name' => self::PRODUCT_GRID_NAME,
            'grid_config' => [
                self::PRODUCT_GRID_NAME
            ],
            'theme_name' => $this->container->get(DataGridThemeHelper::class)
                ->getTheme('order-products-previously-purchased-grid')
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            DataGridThemeHelper::class,
        ]);
    }
}
