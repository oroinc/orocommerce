<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

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

    /**
     * @return Account[]
     */
    public function getPartialAccounts()
    {
        return $this
            ->createQueryBuilder('account')
            ->select('partial account.{id}')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Category $category
     * @param $visibility
     * @return Account[]
     */
    public function getCategoryAccountsByVisibility(Category $category, $visibility)
    {
        $qb = $this->createQueryBuilder('account');

        $qb->select('account')
            ->join(
                'OroB2BAccountBundle:Visibility\AccountCategoryVisibility',
                'accountCategoryVisibility',
                Join::WITH,
                $qb->expr()->eq('accountCategoryVisibility.account', 'account')
            )
            ->where($qb->expr()->eq('accountCategoryVisibility.category', ':category'))
            ->andWhere($qb->expr()->eq('accountCategoryVisibility.visibility', ':visibility'))
            ->setParameters(
                [
                    'category' => $category,
                    'visibility' => $visibility,
                ]
            );

        return $qb->getQuery()->getResult();
    }
}
