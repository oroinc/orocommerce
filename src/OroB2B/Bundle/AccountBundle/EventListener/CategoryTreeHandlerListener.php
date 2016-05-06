<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Visibility\Resolver\CategoryVisibilityResolverInterface;
use OroB2B\Bundle\CatalogBundle\Event\CategoryTreeCreateAfterEvent;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryTreeHandlerListener
{
    /**
     * @var CategoryVisibilityResolverInterface
     */
    protected $categoryVisibilityResolver;

    /**
     * @param CategoryVisibilityResolverInterface $categoryVisibilityResolver
     */
    public function __construct(CategoryVisibilityResolverInterface $categoryVisibilityResolver)
    {
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
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
        // TODO: Use AccountUserRelationsProvider here BB-2988
        $account = $user instanceof AccountUser ? $user->getAccount() : null;
        $categories = $this->filterCategories($event->getCategories(), $account);
        $event->setCategories($categories);
    }

    /**
     * @todo: Add AccountGroup parameter, use it if account is null BB-2988
     * @param Category[] $categories
     * @param Account|null $account
     * @return array
     */
    protected function filterCategories(array $categories, Account $account = null)
    {
        if ($account) {
            $hiddenCategoryIds = $this->categoryVisibilityResolver->getHiddenCategoryIdsForAccount($account);
        } else {
            $hiddenCategoryIds = $this->categoryVisibilityResolver->getHiddenCategoryIds();
        }

        // copy categories array to another variable to prevent loop break on removed elements
        $filteredCategories = $categories;
        foreach ($categories as $category) {
            if (in_array($category->getId(), $hiddenCategoryIds)) {
                $this->removeTreeNode($filteredCategories, $category);
            }
        }

        return $filteredCategories;
    }

    /**
     * @param Category[] $filteredCategories
     * @param Category $category
     */
    protected function removeTreeNode(array &$filteredCategories, Category $category)
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
