<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CatalogBundle\Event\CategoryTreeCreateAfterEvent;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Storage\CategoryVisibilityStorage;

class CategoryTreeHandlerListener
{
    /** @var CategoryVisibilityStorage */
    protected $categoryVisibilityStorage;

    /**
     * @param CategoryVisibilityStorage $categoryVisibilityStorage
     */
    public function __construct(CategoryVisibilityStorage $categoryVisibilityStorage)
    {
        $this->categoryVisibilityStorage = $categoryVisibilityStorage;
    }

    /**
     * @param CategoryTreeCreateAfterEvent $event
     */
    public function onCreateAfter(CategoryTreeCreateAfterEvent $event)
    {
        $user = $event->getUser();
        if ($user instanceof User) {
            return;
        }
        $accountId = $user instanceof AccountUser ? $user->getAccount()->getId() : null;
        $categories = $this->filterCategories($event->getCategories(), $accountId);
        $event->setCategories($categories);
    }

    /**
     * @param array|Category[] $categories
     * @param int|null $accountId
     * @return array
     */
    protected function filterCategories(array $categories, $accountId)
    {
        $visibilityData = $this->categoryVisibilityStorage->getData($accountId);

        $isVisible = $visibilityData->isVisible();
        $ids = $visibilityData->getIds();

        foreach ($categories as &$category) {
            $inIds = in_array($category->getId(), $ids, true);
            if (($isVisible && !$inIds) || (!$isVisible && $inIds)) {
                $this->removeTreeNode($categories, $category);
            }
            $category->getChildCategories()->clear();
        }

        return $categories;
    }

    /**
     * @param array $tree
     * @param Category $category
     */
    protected function removeTreeNode(array &$tree, Category $category)
    {
        foreach ($tree as $id => $item) {
            if ($item === $category) {
                unset($tree[$id]);
            }
        }

        $children = $category->getChildCategories();

        if (!$children->isEmpty()) {
            foreach ($children as $child) {
                $this->removeTreeNode($tree, $child);
            }
        }
    }
}
