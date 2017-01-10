<?php

namespace Oro\Bundle\CustomerBundle\Entity\Repository;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

class CustomerUserAddressRepository extends AbstractDefaultTypedAddressRepository
{
    /**
     * @param CustomerUser $accountUser
     * @param string $type
     * @param AclHelper $aclHelper
     * @return array
     */
    public function getAddressesByType(CustomerUser $accountUser, $type, AclHelper $aclHelper)
    {
        $query = $aclHelper->apply($this->getAddressesByTypeQueryBuilder($accountUser, $type));

        return $query->getResult();
    }

    /**
     * @param CustomerUser $accountUser
     * @param string $type
     * @param AclHelper $aclHelper
     * @return array
     */
    public function getDefaultAddressesByType(CustomerUser $accountUser, $type, AclHelper $aclHelper)
    {
        $query = $aclHelper->apply($this->getDefaultAddressesQueryBuilder($accountUser, $type));

        return $query->getResult();
    }
}
