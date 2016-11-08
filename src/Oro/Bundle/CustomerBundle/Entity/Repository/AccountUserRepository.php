<?php

namespace Oro\Bundle\CustomerBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class AccountUserRepository extends EntityRepository
{
    /**
     * @param AclHelper $aclHelper
     * @return QueryBuilder
     */
    public function getAccountUsersQueryBuilder(AclHelper $aclHelper)
    {
        $criteria = new Criteria();
        $qb = $this->createQueryBuilder('account_user');
        $aclHelper->applyAclToCriteria(
            AccountUser::class,
            $criteria,
            'VIEW',
            ['account' => 'account_user.account']
        );
        return $qb->addCriteria($criteria);
    }
}
