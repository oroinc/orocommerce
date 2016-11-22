<?php

namespace Oro\Bundle\CustomerBundle\Entity;

interface AccountOwnerAwareInterface
{
    /**
     * @return \Oro\Bundle\CustomerBundle\Entity\Account
     */
    public function getAccount();

    /**
     * @return \Oro\Bundle\CustomerBundle\Entity\AccountUser
     */
    public function getAccountUser();
}
