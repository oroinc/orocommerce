<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;

class CategoryBreadcrumbProvider
{
    /**
     * @var CategoryProvider
     */
    protected $categoryProvider;

    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    public function __construct(
        CategoryProvider $categoryProvider,
        LocalizationHelper $localizationHelper,
        Router $router,
        RequestStack $requestStack
    ) {
        $this->categoryProvider   = $categoryProvider;
        $this->localizationHelper = $localizationHelper;
        $this->router             = $router;
        $this->requestStack       = $requestStack;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        $categories  = array_merge(
            $this->categoryProvider->getParentCategories(),
            [$this->categoryProvider->getCurrentCategory()]
        );
        $breadcrumbs = [];

        /* @var Category $category */
        foreach ($categories as $index => $category) {
            if (0 === $index) {
                $url = (string)$this->router->generate('oro_product_frontend_product_index');
            } else {
                $url = (string)$this->router->generate(
                    'oro_product_frontend_product_index',
                    [
                        'categoryId'           => $category->getId(),
                        'includeSubcategories' =>
                            $this->categoryProvider->getIncludeSubcategoriesChoice(true)
                    ]
                );
            }

            $breadcrumbs[] = [
                'label' => (string)$this->localizationHelper
                    ->getLocalizedValue($category->getTitles()),
                'url'   => $url
            ];
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
        $breadcrumbs   = $this->getItems();
        $breadcrumbs[] = ['label' => $currentPageTitle, 'url' => null];

        return $breadcrumbs;
    }
}
