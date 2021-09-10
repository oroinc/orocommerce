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
    /** @var CategoryVisibilityProvider */
    private $categoryVisibilityProvider;

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
        foreach ($categories as $category) {
            if (in_array($category->getId(), $hiddenCategoryIds, true)) {
                $this->removeTreeNode($filteredCategories, $category);
            }
        }

        return $filteredCategories;
    }

    /**
     * @param Category[] $filteredCategories
     * @param Category   $category
     */
    private function removeTreeNode(array &$filteredCategories, Category $category)
    {
        foreach ($filteredCategories as $id => $item) {
            if ($item->getId() === $category->getId()) {
                unset($filteredCategories[$id]);
                break;
            }
        }

        $children = $category->getChildCategories();
        if (!$children->isEmpty()) {
            foreach ($children as $child) {
                $this->removeTreeNode($filteredCategories, $child);
            }
        }
    }
}
