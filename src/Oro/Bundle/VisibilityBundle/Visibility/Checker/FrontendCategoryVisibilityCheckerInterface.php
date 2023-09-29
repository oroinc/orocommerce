<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Checker;

use Oro\Bundle\CatalogBundle\Entity\Category;

/**
 * Represents a service that is used to restricts access to a category on the storefront.
 */
interface FrontendCategoryVisibilityCheckerInterface
{
    public function isCategoryVisible(Category $category): bool;
}
