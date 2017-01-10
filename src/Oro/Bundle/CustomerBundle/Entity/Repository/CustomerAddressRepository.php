<?php

namespace Oro\Bundle\CustomerBundle\Entity\Repository;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\CustomerBundle\Entity\Customer;

class CustomerAddressRepository extends AbstractDefaultTypedAddressRepository
{
    /**
     * @param Customer $account
     * @param string $type
     * @param AclHelper $aclHelper
     * @return array
     */
    public function getAddressesByType(Customer $account, $type, AclHelper $aclHelper)
    {
        $query = $aclHelper->apply($this->getAddressesByTypeQueryBuilder($account, $type));

        return $query->getResult();
    }

    /**
     * @param Customer $account
     * @param string $type
     * @param AclHelper $aclHelper
     * @return array
     */
    public function getDefaultAddressesByType(Customer $account, $type, AclHelper $aclHelper)
    {
        $query = $aclHelper->apply($this->getDefaultAddressesQueryBuilder($account, $type));

        return $query->getResult();
    }
}
