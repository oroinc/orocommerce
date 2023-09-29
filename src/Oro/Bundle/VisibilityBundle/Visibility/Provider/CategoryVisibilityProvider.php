<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Provider;

use Oro\Bundle\CustomerBundle\Entity\CustomerUserInterface;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\VisibilityBundle\Visibility\Resolver\CategoryVisibilityResolverInterface;

/**
 * Provides information about hidden categories.
 */
class CategoryVisibilityProvider
{
    private CategoryVisibilityResolverInterface $categoryVisibilityResolver;
    private CustomerUserRelationsProvider $customerUserRelationsProvider;

    public function __construct(
        CategoryVisibilityResolverInterface $categoryVisibilityResolver,
        CustomerUserRelationsProvider $customerUserRelationsProvider
    ) {
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
        $this->customerUserRelationsProvider = $customerUserRelationsProvider;
    }

    /**
     * @param CustomerUserInterface|null $customerUser
     *
     * @return int[]
     */
    public function getHiddenCategoryIds(?CustomerUserInterface $customerUser): array
    {
        $customer = $this->customerUserRelationsProvider->getCustomer($customerUser);
        if (null !== $customer) {
            return $this->categoryVisibilityResolver->getHiddenCategoryIdsForCustomer($customer);
        }

        $customerGroup = $this->customerUserRelationsProvider->getCustomerGroup($customerUser);
        if (null !== $customerGroup) {
            return $this->categoryVisibilityResolver->getHiddenCategoryIdsForCustomerGroup($customerGroup);
        }

        return $this->categoryVisibilityResolver->getHiddenCategoryIds();
    }
}
