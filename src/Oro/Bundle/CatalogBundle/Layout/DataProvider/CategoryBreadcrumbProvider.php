<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Symfony\Component\Routing\Router;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

class CategoryBreadcrumbProvider
{
    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @param CategoryProvider   $categoryProvider
     * @param LocalizationHelper $localizationHelper
     * @param Router             $router
     */
    public function __construct(
        CategoryProvider $categoryProvider,
        LocalizationHelper $localizationHelper,
        Router $router
    ) {
        $this->categoryProvider   = $categoryProvider;
        $this->localizationHelper = $localizationHelper;
        $this->router             = $router;
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
                            $this->categoryProvider->getIncludeSubcategoriesChoice()
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
}
