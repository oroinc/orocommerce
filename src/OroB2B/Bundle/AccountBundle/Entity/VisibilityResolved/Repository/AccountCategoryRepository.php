<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * Composite primary key fields order:
 *  - account
 *  - category
 */
class AccountCategoryRepository extends EntityRepository
{
    use CategoryVisibilityResolvedTermTrait;

    /**
     * @param Category $category
     * @param Account $account
     * @param int $configValue
     * @return bool
     */
    public function isCategoryVisible(Category $category, Account $account, $configValue)
    {
        $accountGroup = $account->getGroup();

        $qb = $this->_em->createQueryBuilder();

        $configValue = $qb->expr()->literal($configValue);
        $accountCondition = sprintf(
            'CASE WHEN acvr.visibility = %s THEN COALESCE(cvr.visibility, %s) ELSE acvr.visibility END',
            AccountCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL,
            $configValue
        );

        $qb->select('COALESCE(' . $accountCondition . ', cvr.visibility, ' . $configValue . ')')
            ->from('OroB2BCatalogBundle:Category', 'category')
            ->leftJoin(
                'OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved',
                'cvr',
                Join::WITH,
                $qb->expr()->eq('cvr.category', 'category')
            );

        if ($accountGroup) {
            $qb->select('COALESCE(' . $accountCondition . ', agcvr.visibility, cvr.visibility, ' . $configValue . ')')
                ->leftJoin(
                    'OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved',
                    'agcvr',
                    Join::WITH,
                    $qb->expr()->andX(
                        $qb->expr()->eq('agcvr.category', 'category'),
                        $qb->expr()->eq('agcvr.accountGroup', ':accountGroup')
                    )
                )
                ->setParameter('accountGroup', $accountGroup);
        }

        $qb
            ->leftJoin(
                'OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved',
                'acvr',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('acvr.category', 'category'),
                    $qb->expr()->eq('acvr.account', ':account')
                )
            )
            ->where($qb->expr()->eq('category', ':category'))
            ->setParameter('category', $category)
            ->setParameter('account', $account);

        $visibility = $qb->getQuery()->getSingleScalarResult();

        return (int)$visibility === BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }

    /**
     * @param int $visibility
     * @param Account $account
     * @param int $configValue
     * @return array
     */
    public function getCategoryIdsByVisibility($visibility, Account $account, $configValue)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('category.id')
            ->from('OroB2BCatalogBundle:Category', 'category')
            ->orderBy('category.id');

        $terms = [$this->getCategoryVisibilityResolvedTerm($qb, $configValue)];
        if ($account->getGroup()) {
            $terms[] = $this->getAccountGroupCategoryVisibilityResolvedTerm($qb, $account->getGroup());
        }
        $terms[] = $this->getAccountCategoryVisibilityResolvedTerm($qb, $account, $configValue);

        if ($visibility === BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE) {
            $qb->andWhere($qb->expr()->gt(implode(' + ', $terms), 0));
        } else {
            $qb->andWhere($qb->expr()->lte(implode(' + ', $terms), 0));
        }

        $categoryVisibilityResolved = $qb->getQuery()->getArrayResult();

        return array_map('current', $categoryVisibilityResolved);
    }
}
