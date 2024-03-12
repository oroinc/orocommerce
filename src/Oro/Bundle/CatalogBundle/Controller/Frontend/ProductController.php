<?php

namespace Oro\Bundle\CatalogBundle\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
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
     *
     * @return array
     */
    #[Route(path: '/allproducts', name: 'oro_catalog_frontend_product_allproducts')]
    #[Layout(vars: ['entity_class', 'grid_config', 'theme_name', 'filters_position'])]
    #[AclAncestor('oro_product_frontend_view')]
    public function allProductsAction()
    {
        return [
            'entity_class' => Product::class,
            'grid_config' => [
                'frontend-catalog-allproducts-grid',
            ],
            'theme_name' => $this->container->get(DataGridThemeHelper::class)
                ->getTheme('frontend-catalog-allproducts-grid'),
            'filters_position' => $this->container->get(ConfigManager::class)
                ->get(Configuration::getConfigKeyByName('filters_position')),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            DataGridThemeHelper::class,
            ConfigManager::class,
        ]);
    }
}
