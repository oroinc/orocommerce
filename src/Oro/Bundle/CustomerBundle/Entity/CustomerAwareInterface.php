<?php

namespace Oro\Bundle\CustomerBundle\Entity;

interface CustomerAwareInterface
{
    /**
     * @return Customer
     */
    public function getCustomer();

    /**
     *
     * @param Customer $account
     * @return $this
     */
    public function setCustomer(Customer $account);
}
