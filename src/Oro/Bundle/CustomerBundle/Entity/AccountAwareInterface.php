<?php

namespace Oro\Bundle\CustomerBundle\Entity;

/**
 * @deprecated Use CustomerAwareInterface
 */
interface AccountAwareInterface
{
    /**
     * @return Customer
     */
    public function getAccount();

    /**
     *
     * @param Customer $account
     * @return $this
     */
    public function setAccount(Customer $account);
}
