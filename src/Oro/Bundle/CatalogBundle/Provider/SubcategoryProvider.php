<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Provides available subcategories for a given category.
 *
 * Retrieves subcategories that are accessible to the current user, leveraging the category tree
 * provider to ensure proper security and visibility filtering.
 */
class SubcategoryProvider
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var CategoryTreeProvider */
    protected $categoryProvider;

    public function __construct(TokenAccessorInterface $tokenAccessor, CategoryTreeProvider $categoryProvider)
    {
        $this->tokenAccessor = $tokenAccessor;
        $this->categoryProvider = $categoryProvider;
    }

    /**
     * @param Category|null $category
     * @return array|Category[]
     */
    public function getAvailableSubcategories(?Category $category = null)
    {
        if (!$category) {
            return [];
        }

        return array_values(
            $this->categoryProvider->getCategories($this->tokenAccessor->getUser(), $category, false)
        );
    }
}
