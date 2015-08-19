<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class AccountUserAddressRepository extends AbstractDefaultTypedAddressRepository
{
    /**
     * @param AccountUser $accountUser
     * @param string $type
     * @param AclHelper $aclHelper
     * @return array
     */
    public function getAddressesByType(AccountUser $accountUser, $type, AclHelper $aclHelper)
    {
        $query = $aclHelper->apply($this->getAddressesByTypeQueryBuilder($accountUser, $type));

        return $query->getResult();
    }

    /**
     * @param AccountUser $accountUser
     * @param string $type
     * @param AclHelper $aclHelper
     * @return array
     */
    public function getDefaultAddressesByType(AccountUser $accountUser, $type, AclHelper $aclHelper)
    {
        $query = $aclHelper->apply($this->getDefaultAddressesQueryBuilder($accountUser, $type));

        return $query->getResult();
    }
}
