<?php

namespace Oro\Bundle\VisibilityBundle\Driver;

use Oro\Bundle\CustomerBundle\Entity\Customer;

interface CustomerPartialUpdateDriverInterface
{
    /**
     * Inserts customer visibility field for indexed entities when value in "is visible by default" field
     * is not equal to value in "visibility new" field.
     *
     * @param Customer $customer
     */
    public function createCustomerWithoutCustomerGroupVisibility(Customer $customer);

    /**
     * Updates customer visibility field for indexed entities with actual values.
     *
     * @param Customer $customer
     */
    public function updateCustomerVisibility(Customer $customer);

    /**
     * Deletes customer visibility field for all indexed entities.
     *
     * @param Customer $customer
     */
    public function deleteCustomerVisibility(Customer $customer);
}
