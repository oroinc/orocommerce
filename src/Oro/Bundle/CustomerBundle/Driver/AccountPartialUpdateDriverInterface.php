<?php

namespace Oro\Bundle\CustomerBundle\Driver;

use Oro\Bundle\CustomerBundle\Entity\Account;

interface AccountPartialUpdateDriverInterface
{
    /**
     * @param Account $account
     */
    public function createAccountWithoutAccountGroupVisibility(Account $account);

    /**
     * @param Account $account
     */
    public function updateAccountVisibility(Account $account);

    /**
     * @param Account $account
     */
    public function deleteAccountVisibility(Account $account);
}
