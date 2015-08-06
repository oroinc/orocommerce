<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class AccountRepository extends EntityRepository
{
    /**
     * @param AclHelper $aclHelper
     * @param int $accountId
     * @return array
     */
    public function getChildrenIds(AclHelper $aclHelper, $accountId)
    {
        $qb = $this->createQueryBuilder('account');
        $qb->select('account.id as account_id')
            ->where($qb->expr()->eq('IDENTITY(account.parent)', ':parent'))
            ->setParameter('parent', $accountId);
        $result = $aclHelper->apply($qb)->getArrayResult();
        $result = array_map(
            function ($item) {
                return $item['account_id'];
            },
            $result
        );
        $children = $result;

        if ($result) {
            foreach ($result as $childId) {
                $children = array_merge($children, $this->getChildrenIds($aclHelper, $childId));
            }
        }

        return $children;
    }
}
