<?php

namespace OroB2B\Bundle\AccountBundle\Event;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use Symfony\Component\EventDispatcher\Event;

class AccountGroupEvent extends Event
{
    const PRE_REMOVE = 'orob2b_account.account_group.pre_remove';

    /**
     * @var  AccountGroup
     */
    protected $accountGroup;

    /**
     * @param AccountGroup $accountGroup
     */
    public function __construct(AccountGroup $accountGroup)
    {
        $this->accountGroup = $accountGroup;
    }

    /**
     * @return AccountGroup
     */
    public function getAccountGroup()
    {
        return $this->accountGroup;
    }
}
