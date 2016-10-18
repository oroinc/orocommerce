<?php

namespace Oro\Bundle\CustomerBundle\Entity;

interface AccountGroupAwareInterface
{
    /**
     * @return AccountGroup
     */
    public function getAccountGroup();

    /**
     *
     * @param AccountGroup $accountGroup
     * @return $this
     */
    public function setAccountGroup(AccountGroup $accountGroup);
}
