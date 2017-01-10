<?php

namespace Oro\Bundle\CustomerBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\CustomerBundle\Entity\Customer;

class AccountEvent extends Event
{
    const ON_ACCOUNT_GROUP_CHANGE = 'oro_customer.account.on_account_group_change';

    /**
     * @var  Customer
     */
    protected $account;

    /**
     * @param Customer $account
     */
    public function __construct(Customer $account)
    {
        $this->account = $account;
    }

    /**
     * @return Customer
     */
    public function getAccount()
    {
        return $this->account;
    }
}
