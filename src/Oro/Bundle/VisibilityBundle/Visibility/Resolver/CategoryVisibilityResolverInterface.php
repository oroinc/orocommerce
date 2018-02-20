<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Resolver;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;

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
