<?php

namespace OroB2B\Bundle\CatalogBundle\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\AbstractServerRenderDataProvider;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Provider\CategoryTreeProvider as CategoriesProvider;

class FeaturedCategoriesProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var array
     */
    protected $data;

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
     * @param ContextInterface $context
     * @return Category[]
     */
    public function getData(ContextInterface $context)
    {
        if (!$this->data) {
            $this->data = $this->getFeaturedCategories();
        }

        return $this->data;
    }

    public function getFeaturedCategories()
    {
        $categories = $this->categoryTreeProvider->getCategories(null);
        return array_filter($categories, function(Category $category) {
            return $category->getLevel() !== 0;
        });
    }
}
