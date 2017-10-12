<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class SubcategoryProvider
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var CategoryTreeProvider */
    protected $categoryProvider;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     * @param CategoryTreeProvider $categoryProvider
     */
    public function __construct(TokenAccessorInterface $tokenAccessor, CategoryTreeProvider $categoryProvider)
    {
        $this->tokenAccessor = $tokenAccessor;
        $this->categoryProvider = $categoryProvider;
    }

    /**
     * @param null|Category $category
     * @return array|Category[]
     */
    public function getAvailableSubcategories(Category $category = null)
    {
        if (!$category) {
            return [];
        }

        $categories = array_values(
            array_filter(
                $this->categoryProvider->getCategories($this->tokenAccessor->getUser(), $category, false),
                function (Category $item) use ($category) {
                    return $item->getParentCategory() && $item->getParentCategory()->getId() === $category->getId();
                }
            )
        );

        return $categories;
    }
}
