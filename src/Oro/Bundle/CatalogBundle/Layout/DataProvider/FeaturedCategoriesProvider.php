<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider as CategoriesProvider;

class FeaturedCategoriesProvider
{
    /**
     * @var Category[]
     */
    protected $categories;

    /**
     * @var CategoriesProvider
     */
    protected $categoryTreeProvider;

    /**
     * @param CategoriesProvider $categoryTreeProvider
     */
    public function __construct(CategoriesProvider $categoryTreeProvider)
    {
        $this->categoryTreeProvider = $categoryTreeProvider;
    }

    /**
     * @return Category[]
     */
    public function getAll()
    {
        $this->setCategories();

        return $this->categories;
    }

    protected function setCategories()
    {
        if ($this->categories !== null) {
            return;
        }

        $categories = $this->categoryTreeProvider->getCategories(null);
        $this->categories = array_filter($categories, function (Category $category) {
            return $category->getLevel() !== 0;
        });
    }
}
