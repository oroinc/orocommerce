<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Provider\AccountUserRelationsProvider;
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
     * @var AccountUserRelationsProvider
     */
    protected $accountUserRelationsProvider;

    /**
     * @param CategoryVisibilityResolverInterface $categoryVisibilityResolver
     * @param AccountUserRelationsProvider $accountUserRelationsProvider
     */
    public function __construct(
        CategoryVisibilityResolverInterface $categoryVisibilityResolver,
        AccountUserRelationsProvider $accountUserRelationsProvider
    ) {
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
        $this->accountUserRelationsProvider = $accountUserRelationsProvider;
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

        $account = $this->accountUserRelationsProvider->getAccount($user);
        $accountGroup = $this->accountUserRelationsProvider->getAccountGroup($user);
        $categories = $this->filterCategories($event->getCategories(), $account, $accountGroup);
        $event->setCategories($categories);
    }

    /**
     * @param Category[] $categories
     * @param Account|null $account
     * @param AccountGroup|null $accountGroup
     * @return array
     */
    protected function filterCategories(array $categories, Account $account = null, AccountGroup $accountGroup = null)
    {
        if ($account) {
            $hiddenCategoryIds = $this->categoryVisibilityResolver->getHiddenCategoryIdsForAccount($account);
        } elseif ($accountGroup) {
            $hiddenCategoryIds = $this->categoryVisibilityResolver->getHiddenCategoryIdsForAccountGroup($accountGroup);
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
