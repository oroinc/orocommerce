<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
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

    public function getItems(): array
    {
        $breadcrumbs = $this->getAllCategoryItems();

        array_pop($breadcrumbs);

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
        $this->requestStack->getCurrentRequest()?->attributes->set('categoryId', (int) $categoryId);

        return $this->getAllCategoryItems();
    }

    private function getAllCategoryItems(): array
    {
        $breadcrumbs = [];
        $categories = $this->categoryProvider->getCategoryPath();

        foreach ($categories as $index => $category) {
            $url = $index === 0
                ? $this->urlGenerator->generate('oro_product_frontend_product_index')
                : $this->urlGenerator->generate(
                    'oro_product_frontend_product_index',
                    [
                        'categoryId' => $category->getId(),
                        'includeSubcategories' => $this->categoryProvider->getIncludeSubcategoriesChoice(true)
                    ]
                );

            $breadcrumbs[] = [
                'label' => (string) $this->localizationHelper->getLocalizedValue($category->getTitles()),
                'url' => $url
            ];
        }

        return $breadcrumbs;
    }
}
