<?php

namespace Oro\Bundle\CustomerBundle\Entity;

interface AccountAwareInterface
{
    /**
     * @return Account
     */
    public function getAccount();

    /**
     *
     * @param Account $account
     * @return $this
     */
    public function setAccount(Account $account);
}
