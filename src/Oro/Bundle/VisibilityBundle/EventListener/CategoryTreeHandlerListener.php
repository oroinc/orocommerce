<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Event\CategoryTreeCreateAfterEvent;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\VisibilityBundle\Visibility\Resolver\CategoryVisibilityResolverInterface;

class CategoryTreeHandlerListener
{
    /**
     * @var CategoryVisibilityResolverInterface
     */
    protected $categoryVisibilityResolver;

    /**
     * @var CustomerUserRelationsProvider
     */
    protected $customerUserRelationsProvider;

    /**
     * @param CategoryVisibilityResolverInterface $categoryVisibilityResolver
     * @param CustomerUserRelationsProvider $customerUserRelationsProvider
     */
    public function __construct(
        CategoryVisibilityResolverInterface $categoryVisibilityResolver,
        CustomerUserRelationsProvider $customerUserRelationsProvider
    ) {
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
        $this->customerUserRelationsProvider = $customerUserRelationsProvider;
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

        $customer = $this->customerUserRelationsProvider->getCustomer($user);
        $customerGroup = $this->customerUserRelationsProvider->getCustomerGroup($user);
        $categories = $this->filterCategories($event->getCategories(), $customer, $customerGroup);
        $event->setCategories($categories);
    }

    /**
     * @param Category[] $categories
     * @param Customer|null $customer
     * @param CustomerGroup|null $customerGroup
     * @return array
     */
    protected function filterCategories(
        array $categories,
        Customer $customer = null,
        CustomerGroup $customerGroup = null
    ) {
        if ($customer) {
            $hiddenCategoryIds = $this->categoryVisibilityResolver->getHiddenCategoryIdsForCustomer($customer);
        } elseif ($customerGroup) {
            $hiddenCategoryIds = $this->categoryVisibilityResolver->getHiddenCategoryIdsForCustomerGroup(
                $customerGroup
            );
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
