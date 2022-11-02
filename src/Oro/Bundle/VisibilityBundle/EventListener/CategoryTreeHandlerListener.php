<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Event\CategoryTreeCreateAfterEvent;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\VisibilityBundle\Visibility\Provider\CategoryVisibilityProvider;

/**
 * Removes hidden categories from a category tree.
 */
class CategoryTreeHandlerListener
{
    private CategoryVisibilityProvider $categoryVisibilityProvider;

    public function __construct(CategoryVisibilityProvider $categoryVisibilityProvider)
    {
        $this->categoryVisibilityProvider = $categoryVisibilityProvider;
    }

    public function onCreateAfter(CategoryTreeCreateAfterEvent $event)
    {
        $user = $event->getUser();
        if ($user instanceof User) {
            return;
        }

        $hiddenCategoryIds = $this->categoryVisibilityProvider->getHiddenCategoryIds($user);
        if ($hiddenCategoryIds) {
            $event->setCategories(
                $this->filterCategories($event->getCategories(), $hiddenCategoryIds)
            );
        }
    }

    /**
     * @param Category[] $categories
     * @param int[]      $hiddenCategoryIds
     *
     * @return array
     */
    private function filterCategories(array $categories, array $hiddenCategoryIds)
    {
        // copy categories array to another variable to prevent loop break on removed elements
        $filteredCategories = $categories;
        foreach ($categories as $key => $category) {
            if (in_array($category->getId(), $hiddenCategoryIds, true)) {
                unset($filteredCategories[$key]);
            }
        }

        return $filteredCategories;
    }
}
