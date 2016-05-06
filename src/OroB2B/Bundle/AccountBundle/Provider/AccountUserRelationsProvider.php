<?php

namespace OroB2B\Bundle\AccountBundle\Provider;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class AccountUserRelationsProvider
{
    /**
     * @param AccountUser|null $accountUser
     * @return null|Account
     */
    public function getAccount(AccountUser $accountUser = null)
    {
        if ($accountUser) {
            return $accountUser->getAccount();
        }

        return null;
    }

    /**
     * @param AccountUser|null $accountUser
     * @return null|AccountGroup
     */
    public function getAccountGroup(AccountUser $accountUser = null)
    {
        if ($accountUser) {
            $account = $this->getAccount($accountUser);
            if ($account) {
                return $account->getGroup();
            }
        } else {
            // TODO: Get for anonymous user, BB-2986
        }

        return null;
    }
}
