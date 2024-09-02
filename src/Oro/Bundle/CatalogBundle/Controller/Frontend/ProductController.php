<?php

namespace Oro\Bundle\CatalogBundle\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for view all products on the storefront.
 */
class ProductController extends AbstractController
{
    #[Route(path: '/allproducts', name: 'oro_catalog_frontend_product_allproducts')]
    #[Layout(vars: ['entity_class', 'grid_config', 'theme_name', 'filters_position'])]
    #[AclAncestor('oro_product_frontend_view')]
    public function allProductsAction(): array
    {
        return [
            'entity_class' => Product::class,
            'grid_config' => [
                'frontend-catalog-allproducts-grid',
            ],
            'theme_name' => $this->container->get(DataGridThemeHelper::class)
                ->getTheme('frontend-catalog-allproducts-grid'),
            'filters_position' => $this->getFiltersPosition(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            DataGridThemeHelper::class,
            ConfigManager::class,
            ThemeConfigurationProvider::class,
        ]);
    }

    private function getFiltersPosition(): string
    {
        /** @var ThemeConfigurationProvider $themeConfigurationProvider */
        $themeConfigurationProvider = $this->container->get(ThemeConfigurationProvider::class);

        $themeConfigurationOptionKey = ThemeConfiguration::buildOptionKey('product_listing', 'filters_position');
        if ($themeConfigurationProvider->hasThemeConfigurationOption($themeConfigurationOptionKey)) {
            return $themeConfigurationProvider->getThemeConfigurationOption($themeConfigurationOptionKey);
        }

        return $this->container->get(ConfigManager::class)->get(Configuration::getConfigKeyByName('filters_position'));
    }
}
