<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;

trait CategoryVisibilityResolvedTermTrait
{
    /**
     * @param QueryBuilder $qb
     * @param int $configValue
     * @return string
     */
    protected function getCategoryVisibilityResolvedTerm(QueryBuilder $qb, $configValue)
    {
        $qb->leftJoin(
            'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\CategoryVisibilityResolved',
            'cvr',
            Join::WITH,
            $qb->expr()->eq($this->getRootAlias($qb), 'cvr.category')
        );

        return sprintf('COALESCE(cvr.visibility, %s)', $configValue);
    }

    /**
     * @param QueryBuilder $qb
     * @param AccountGroup $account
     * @return string
     */
    protected function getAccountGroupCategoryVisibilityResolvedTerm(QueryBuilder $qb, AccountGroup $account)
    {
        $qb->leftJoin(
            'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved',
            'agcvr',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq($this->getRootAlias($qb), 'agcvr.category'),
                $qb->expr()->eq('agcvr.accountGroup', ':account_group')
            )
        );

        $qb->setParameter('account_group', $account);

        return 'COALESCE(agcvr.visibility, 0) * 10';
    }

    /**
     * @param QueryBuilder $qb
     * @param Account $account
     * @return string
     */
    protected function getAccountCategoryVisibilityResolvedTerm(QueryBuilder $qb, Account $account)
    {
        $qb->leftJoin(
            'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved',
            'acvr',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq($this->getRootAlias($qb), 'acvr.category'),
                $qb->expr()->eq('acvr.account', ':account')
            )
        );

        $qb->setParameter('account', $account);

        $term = <<<TERM
CASE WHEN acvr.visibility = %s
    THEN (cvr.visibility * 100)
ELSE (COALESCE(acvr.visibility, 0) * 100)
END
TERM;
        return sprintf($term, AccountCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL);
    }

    /**
     * @param QueryBuilder $qb
     * @return mixed
     */
    protected function getRootAlias(QueryBuilder $qb)
    {
        return $qb->getRootAliases()[0];
    }
}
