<?php

namespace Oro\Bundle\VisibilityBundle\Driver;

use Oro\Bundle\CustomerBundle\Entity\Customer;

interface AccountPartialUpdateDriverInterface
{
    /**
     * Inserts account visibility field for indexed entities when value in "is visible by default" field
     * is not equal to value in "visibility new" field.
     *
     * @param Customer $account
     */
    public function createAccountWithoutAccountGroupVisibility(Customer $account);

    /**
     * Updates account visibility field for indexed entities with actual values.
     *
     * @param Customer $account
     */
    public function updateAccountVisibility(Customer $account);

    /**
     * Deletes account visibility field for all indexed entities.
     *
     * @param Customer $account
     */
    public function deleteAccountVisibility(Customer $account);
}
