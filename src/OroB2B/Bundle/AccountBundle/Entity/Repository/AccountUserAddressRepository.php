<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class AccountUserAddressRepository extends AbstractDefaultTypedAddressRepository
{
    /**
     * @param AccountUser $accountUser
     * @param string $type
     * @return array
     */
    public function getAddressesByType(AccountUser $accountUser, $type)
    {
        return $this->getAddressesByTypeQueryBuilder($accountUser, $type)->getQuery()->getResult();
    }

    /**
     * @param AccountUser $accountUser
     * @param string $type
     * @return array
     */
    public function getDefaultAddressesByType(AccountUser $accountUser, $type)
    {
        return $this->getDefaultAddressesQueryBuilder($accountUser, $type)->getQuery()->getResult();
    }
}
