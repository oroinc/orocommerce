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
    /** @var CategoryVisibilityResolverInterface */
    private $categoryVisibilityResolver;

    /** @var CustomerUserRelationsProvider */
    private $customerUserRelationsProvider;

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
