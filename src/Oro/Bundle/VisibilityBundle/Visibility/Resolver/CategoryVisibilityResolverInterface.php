<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Resolver;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;

/**
 * Defines the contract for resolving category visibility for different customer contexts.
 *
 * Implementations of this interface provide methods to determine whether categories are visible for all customers,
 * specific customer groups, or individual customers. They resolve the visibility fallback chain
 * and return the final visibility state, as well as provide lists of visible and hidden category IDs
 * for efficient filtering in queries.
 */
interface CategoryVisibilityResolverInterface
{
    /**
     * @param Category $category
     * @return bool
     */
    public function isCategoryVisible(Category $category);

    /**
     * @return array
     */
    public function getVisibleCategoryIds();

    /**
     * @return array
     */
    public function getHiddenCategoryIds();

    /**
     * @param Category $category
     * @param CustomerGroup $customerGroup
     * @return bool
     */
    public function isCategoryVisibleForCustomerGroup(Category $category, CustomerGroup $customerGroup);

    /**
     * @param CustomerGroup $customerGroup
     * @return array
     */
    public function getVisibleCategoryIdsForCustomerGroup(CustomerGroup $customerGroup);

    /**
     * @param CustomerGroup $customerGroup
     * @return array
     */
    public function getHiddenCategoryIdsForCustomerGroup(CustomerGroup $customerGroup);

    /**
     * @param Category $category
     * @param Customer $customer
     * @return bool
     */
    public function isCategoryVisibleForCustomer(Category $category, Customer $customer);

    /**
     * @param Customer $customer
     * @return array
     */
    public function getVisibleCategoryIdsForCustomer(Customer $customer);

    /**
     * @param Customer $customer
     * @return array
     */
    public function getHiddenCategoryIdsForCustomer(Customer $customer);
}
