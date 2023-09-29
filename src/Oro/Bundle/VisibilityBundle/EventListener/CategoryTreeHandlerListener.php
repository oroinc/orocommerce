<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

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

    public function onCreateAfter(CategoryTreeCreateAfterEvent $event): void
    {
        $user = $event->getUser();
        if ($user instanceof User) {
            return;
        }

        $hiddenCategoryIds = $this->categoryVisibilityProvider->getHiddenCategoryIds($user);
        if (!$hiddenCategoryIds) {
            return;
        }

        $hasChanges = false;
        $categories = $event->getCategories();
        $filteredCategories = $categories;
        $hiddenCategoryIds = array_fill_keys($hiddenCategoryIds, true);
        foreach ($categories as $key => $category) {
            if (isset($hiddenCategoryIds[$category->getId()])) {
                unset($filteredCategories[$key]);
                $hasChanges = true;
            }
        }
        if ($hasChanges) {
            $event->setCategories(array_values($filteredCategories));
        }
    }
}
