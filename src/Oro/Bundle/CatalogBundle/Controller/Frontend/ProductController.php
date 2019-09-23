<?php

namespace Oro\Bundle\CatalogBundle\Controller\Frontend;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * View all products on front store
 */
class ProductController extends AbstractController
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
        return [
            'entity_class' => Product::class,
            'grid_config' => [
                'frontend-catalog-allproducts-grid'
            ],
            'theme_name' => $this->get(DataGridThemeHelper::class)
                ->getTheme('frontend-catalog-allproducts-grid')
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
