<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountAddressRepository extends AbstractDefaultTypedAddressRepository
{
    /**
     * @param Account $account
     * @param string $type
     * @return array
     */
    public function getAddressesByType(Account $account, $type)
    {
        return $this->getAddressesByTypeQueryBuilder($account, $type)->getQuery()->getResult();
    }

    /**
     * @param Account $account
     * @param string $type
     * @return array
     */
    public function getDefaultAddressesByType(Account $account, $type)
    {
        return $this->getDefaultAddressesQueryBuilder($account, $type)->getQuery()->getResult();
    }
}
