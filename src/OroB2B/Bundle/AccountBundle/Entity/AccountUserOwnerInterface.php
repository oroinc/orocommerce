<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

interface AccountUserOwnerInterface
{
    /**
     * @return Account
     */
    public function getAccount();

    /**
     * @return AccountUser
     */
    public function getAccountUser();
}
