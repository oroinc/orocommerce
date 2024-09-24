<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Checker;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Resolver\CategoryVisibilityResolverInterface;

/**
 * Restricts access to a category based on its visibility settings.
 */
class FrontendCategoryVisibilityChecker implements FrontendCategoryVisibilityCheckerInterface
{
    private TokenAccessorInterface $tokenAccessor;
    private CategoryVisibilityResolverInterface $categoryVisibilityResolver;
    private CustomerUserRelationsProvider $customerUserRelationsProvider;

    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        CategoryVisibilityResolverInterface $categoryVisibilityResolver,
        CustomerUserRelationsProvider $customerUserRelationsProvider
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
        $this->customerUserRelationsProvider = $customerUserRelationsProvider;
    }

    #[\Override]
    public function isCategoryVisible(Category $category): bool
    {
        $user = $this->tokenAccessor->getUser();
        $customer = $this->customerUserRelationsProvider->getCustomer($user);
        if (null !== $customer) {
            return $this->categoryVisibilityResolver->isCategoryVisibleForCustomer($category, $customer);
        }

        $customerGroup = $this->customerUserRelationsProvider->getCustomerGroup($user);
        if (null !== $customerGroup) {
            return $this->categoryVisibilityResolver->isCategoryVisibleForCustomerGroup($category, $customerGroup);
        }

        return $this->categoryVisibilityResolver->isCategoryVisible($category);
    }
}
