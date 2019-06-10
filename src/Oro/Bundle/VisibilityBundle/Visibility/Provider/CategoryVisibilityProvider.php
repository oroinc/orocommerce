<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Provider;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\VisibilityBundle\Visibility\Resolver\CategoryVisibilityResolverInterface;

/**
 * Provides information about hidden categories.
 */
class CategoryVisibilityProvider
{
    /** @var CategoryVisibilityResolverInterface */
    private $categoryVisibilityResolver;

    /** @var CustomerUserRelationsProvider */
    private $customerUserRelationsProvider;

    /**
     * @param CategoryVisibilityResolverInterface $categoryVisibilityResolver
     * @param CustomerUserRelationsProvider       $customerUserRelationsProvider
     */
    public function __construct(
        CategoryVisibilityResolverInterface $categoryVisibilityResolver,
        CustomerUserRelationsProvider $customerUserRelationsProvider
    ) {
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
        $this->customerUserRelationsProvider = $customerUserRelationsProvider;
    }

    /**
     * @param CustomerUser|null $customerUser
     *
     * @return int[]
     */
    public function getHiddenCategoryIds(?CustomerUser $customerUser): array
    {
        $customer = $this->customerUserRelationsProvider->getCustomer($customerUser);
        if ($customer) {
            return $this->categoryVisibilityResolver->getHiddenCategoryIdsForCustomer($customer);
        }

        $customerGroup = $this->customerUserRelationsProvider->getCustomerGroup($customerUser);
        if ($customerGroup) {
            return $this->categoryVisibilityResolver->getHiddenCategoryIdsForCustomerGroup($customerGroup);
        }

        return $this->categoryVisibilityResolver->getHiddenCategoryIds();
    }
}
