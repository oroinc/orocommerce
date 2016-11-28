<?php

namespace Oro\Bundle\CustomerBundle\Entity;

/**
 * @deprecated Use CustomerOwnerAwareInterface
 */
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
