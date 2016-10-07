<?php

namespace Oro\Bundle\WebsiteSearchBundle\Driver;

use Oro\Bundle\AccountBundle\Entity\Account;

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
