<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

interface AccountOwnerAwareInterface
{
    /**
     * @return \OroB2B\Bundle\AccountBundle\Entity\Account
     */
    public function getAccount();

    /**
     * @return \OroB2B\Bundle\AccountBundle\Entity\AccountUser
     */
    public function getAccountUser();
}
