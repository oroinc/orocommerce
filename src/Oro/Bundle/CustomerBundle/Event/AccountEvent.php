<?php

namespace Oro\Bundle\CustomerBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\CustomerBundle\Entity\Account;

class AccountEvent extends Event
{
    const ON_ACCOUNT_GROUP_CHANGE = 'oro_customer.account.on_account_group_change';

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
