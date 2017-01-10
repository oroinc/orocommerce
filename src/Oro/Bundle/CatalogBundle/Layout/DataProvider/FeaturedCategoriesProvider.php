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
     * @param array $categoryIds
     * @return Category[]
     */
    public function getAll(array $categoryIds = [])
    {
        $this->setCategories($categoryIds);

        return $this->categories;
    }

    /**
     * @param array $categoryIds
     * @return Category[]
     */
    protected function setCategories(array $categoryIds = [])
    {
        if ($this->categories !== null) {
            return;
        }

        $categories = $this->categoryTreeProvider->getCategories(null);
        $this->categories = array_filter($categories, function (Category $category) use ($categoryIds) {
            if ($categoryIds && !in_array($category->getId(), $categoryIds, true)) {
                return false;
            }
            return $category->getLevel() !== 0;
        });
    }
}
