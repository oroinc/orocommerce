<?php

namespace Oro\Bundle\AccountBundle\Entity;

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
