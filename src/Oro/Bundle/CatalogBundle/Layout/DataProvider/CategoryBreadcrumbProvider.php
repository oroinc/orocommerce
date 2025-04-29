<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\DependencyInjection\Configuration as CatalogConfiguration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration as ProductConfiguration;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides breadcrumb items for product categories.
 */
class CategoryBreadcrumbProvider
{
    private CategoryProvider $categoryProvider;
    private LocalizationHelper $localizationHelper;
    private UrlGeneratorInterface $urlGenerator;
    private RequestStack $requestStack;
    private ?ConfigManager $configManager = null;

    public function __construct(
        CategoryProvider $categoryProvider,
        LocalizationHelper $localizationHelper,
        UrlGeneratorInterface $urlGenerator,
        RequestStack $requestStack
    ) {
        $this->categoryProvider = $categoryProvider;
        $this->localizationHelper = $localizationHelper;
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
    }

    public function setConfigManager(ConfigManager $configManager): void
    {
        $this->configManager = $configManager;
    }

    public function getItems(): array
    {
        $breadcrumbs = $this->getAllCategoryItems();

        if ($this->shouldBreadcrumbsBeEmpty($breadcrumbs)) {
            return [];
        }
        if ($this->configManager?->get(CatalogConfiguration::getConfigKeyByName(
            CatalogConfiguration::EXCLUDE_CURRENT_BREADCRUMB_ON_ALL_PAGES
        ))) {
            array_pop($breadcrumbs);
        }

        return $breadcrumbs;
    }

    /**
     * @param int    $categoryId
     * @param string $currentPageTitle
     *
     * @return array
     */
    public function getItemsForProduct($categoryId, $currentPageTitle)
    {
        $this->requestStack->getCurrentRequest()->attributes->set('categoryId', (int) $categoryId);
        $breadcrumbs   = $this->getAllCategoryItems();
        $breadcrumbs[] = ['label' => $currentPageTitle, 'url' => null];

        if ($this->shouldBreadcrumbsBeEmpty($breadcrumbs)) {
            return [];
        }

        if ($this->configManager?->get(ProductConfiguration::getConfigKeyByName(
            ProductConfiguration::EXCLUDE_CURRENT_BREADCRUMB_ON_PRODUCT_VIEW
        ))) {
            array_pop($breadcrumbs);
        }

        return $breadcrumbs;
    }

    private function getAllCategoryItems(): array
    {
        $breadcrumbs = [];
        $categories = $this->categoryProvider->getCategoryPath();

        foreach ($categories as $index => $category) {
            $url = $index === 0
                ? $this->urlGenerator->generate('oro_product_frontend_product_index')
                : $this->urlGenerator->generate('oro_product_frontend_product_index', [
                    'categoryId' => $category->getId(),
                    'includeSubcategories' => $this->categoryProvider->getIncludeSubcategoriesChoice(true)
                ]);

            $breadcrumbs[] = [
                'label' => (string) $this->localizationHelper->getLocalizedValue($category->getTitles()),
                'url'   => $url
            ];
        }

        return $breadcrumbs;
    }

    private function shouldBreadcrumbsBeEmpty(array $breadcrumbs): bool
    {
        return count($breadcrumbs) === 1 && $this->configManager?->get(
            CatalogConfiguration::getConfigKeyByName(CatalogConfiguration::REMOVE_SINGLE_BREADCRUMB)
        );
    }
}
