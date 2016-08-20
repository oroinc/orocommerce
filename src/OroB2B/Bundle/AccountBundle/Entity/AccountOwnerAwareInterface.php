<?php

namespace Oro\Bundle\AccountBundle\Entity;

interface AccountOwnerAwareInterface
{
    /**
     * @return \Oro\Bundle\AccountBundle\Entity\Account
     */
    public function getAccount();

    /**
     * @return \Oro\Bundle\AccountBundle\Entity\AccountUser
     */
    public function getAccountUser();
}
