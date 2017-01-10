<?php

namespace Oro\Bundle\CustomerBundle\Entity;

/**
 * @deprecated Use CustomerOwnerAwareInterface
 */
interface AccountOwnerAwareInterface
{
    /**
     * @return \Oro\Bundle\CustomerBundle\Entity\Customer
     */
    public function getAccount();

    /**
     * @return \Oro\Bundle\CustomerBundle\Entity\CustomerUser
     */
    public function getAccountUser();
}
