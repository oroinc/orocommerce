<?php

namespace Oro\Bundle\CustomerBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\CustomerBundle\Entity\Customer;

class CustomerEvent extends Event
{
    const ON_ACCOUNT_GROUP_CHANGE = 'oro_customer.customer.on_customer_group_change';

    /**
     * @var  Customer
     */
    protected $customer;

    /**
     * @param Customer $customer
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }
}
