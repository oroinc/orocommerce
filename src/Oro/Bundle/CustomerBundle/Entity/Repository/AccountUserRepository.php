<?php

namespace Oro\Bundle\CustomerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class AccountUserRepository extends EntityRepository
{
    /**
     * @param AclHelper $aclHelper
     * @return array
     */
    public function getAccountUsers(AclHelper $aclHelper)
    {
        $qb =  $this->createQueryBuilder('account_user');
        $qb = $aclHelper->apply($qb);
        $result = $qb->getArrayResult();

        $checkoutResult = [];
        foreach ($result as $key => $value) {
            $checkoutResult[$value['id']] = $value['username'];
        }
        return $checkoutResult;
    }
}
