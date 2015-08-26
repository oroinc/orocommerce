<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountAddressRepository extends AbstractDefaultTypedAddressRepository
{
    /**
     * @param Account $account
     * @param string $type
     * @param AclHelper $aclHelper
     * @return array
     */
    public function getAddressesByType(Account $account, $type, AclHelper $aclHelper)
    {
        $query = $aclHelper->apply($this->getAddressesByTypeQueryBuilder($account, $type));

        return $query->getResult();
    }

    /**
     * @param Account $account
     * @param string $type
     * @param AclHelper $aclHelper
     * @return array
     */
    public function getDefaultAddressesByType(Account $account, $type, AclHelper $aclHelper)
    {
        $query = $aclHelper->apply($this->getDefaultAddressesQueryBuilder($account, $type));

        return $query->getResult();
    }
}
