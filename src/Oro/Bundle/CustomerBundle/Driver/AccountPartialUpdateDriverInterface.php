<?php

namespace Oro\Bundle\CustomerBundle\Driver;

use Oro\Bundle\CustomerBundle\Entity\Account;

interface AccountPartialUpdateDriverInterface
{
    /**
     * Inserts account visibility field for indexed entities when value in "is visible by default" field
     * is not equal to value in "visibility new" field.
     *
     * @param Account $account
     */
    public function createAccountWithoutAccountGroupVisibility(Account $account);

    /**
     * Updates account visibility field for indexed entities with actual values.
     *
     * @param Account $account
     */
    public function updateAccountVisibility(Account $account);

    /**
     * Deletes account visibility field for all indexed entities.
     *
     * @param Account $account
     */
    public function deleteAccountVisibility(Account $account);
}
