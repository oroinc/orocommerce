<?php

namespace Oro\Bundle\CustomerBundle\Entity\Repository;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\CustomerBundle\Entity\Customer;

class CustomerAddressRepository extends AbstractDefaultTypedAddressRepository
{
    /**
     * @param Customer $customer
     * @param string $type
     * @param AclHelper $aclHelper
     * @return array
     */
    public function getAddressesByType(Customer $customer, $type, AclHelper $aclHelper)
    {
        $query = $aclHelper->apply($this->getAddressesByTypeQueryBuilder($customer, $type));

        return $query->getResult();
    }

    /**
     * @param Customer $customer
     * @param string $type
     * @param AclHelper $aclHelper
     * @return array
     */
    public function getDefaultAddressesByType(Customer $customer, $type, AclHelper $aclHelper)
    {
        $query = $aclHelper->apply($this->getDefaultAddressesQueryBuilder($customer, $type));

        return $query->getResult();
    }
}
