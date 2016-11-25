<?php

namespace Oro\Bundle\CustomerBundle\Entity;

interface CustomerAwareInterface
{
    /**
     * @return Account
     */
    public function getCustomer();

    /**
     *
     * @param Account $account
     * @return $this
     */
    public function setCustomer(Account $account);
}
