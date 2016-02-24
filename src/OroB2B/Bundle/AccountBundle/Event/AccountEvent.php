<?php

namespace OroB2B\Bundle\AccountBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountEvent extends Event
{
    const ON_ACCOUNT_GROUP_CHANGE = 'orob2b_account.account.on_account_group_change';

    /**@var  Account
     */
    protected $account;

    /**
     * @param Account $account
     */
    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }
}
