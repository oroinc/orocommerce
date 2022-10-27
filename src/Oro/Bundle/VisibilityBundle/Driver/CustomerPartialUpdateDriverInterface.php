<?php

namespace Oro\Bundle\VisibilityBundle\Driver;

use Oro\Bundle\CustomerBundle\Entity\Customer;

/**
 * Interface for the partial update driver of the customer visibility in the website search index
 */
interface CustomerPartialUpdateDriverInterface
{
    /**
     * Inserts customer visibility field for indexed entities when value in "is visible by default" field
     * is not equal to value in "visibility new" field.
     */
    public function createCustomerWithoutCustomerGroupVisibility(Customer $customer);

    /**
     * Updates customer visibility field for indexed entities with actual values.
     */
    public function updateCustomerVisibility(Customer $customer);

    /**
     * Deletes customer visibility field for all indexed entities.
     */
    public function deleteCustomerVisibility(Customer $customer);
}
