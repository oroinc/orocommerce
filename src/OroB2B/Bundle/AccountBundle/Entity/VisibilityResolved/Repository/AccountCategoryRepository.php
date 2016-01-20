<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\Account;
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
     * @return int
     */
    public function getCategoryVisibility(Category $category, Account $account, $configValue)
    {
        $accountGroup = $account->getGroup();

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($this->formatConfigFallback('acvr.visibility, cvr.visibility', $configValue))
            ->from('OroB2BCatalogBundle:Category', 'category')
            ->leftJoin(
                'OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved',
                'cvr',
                Join::WITH,
                $qb->expr()->eq('cvr.category', 'category')
            );

        if ($accountGroup) {
            $qb->select($this->formatConfigFallback('acvr.visibility, agcvr.visibility, cvr.visibility', $configValue))
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

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Category $category
     * @param Account $account
     * @param int $configValue
     * @return bool
     */
    public function isCategoryVisible(Category $category, Account $account, $configValue)
    {
        $visibility = $this->getCategoryVisibility($category, $account, $configValue);

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
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('category.id')
            ->from('OroB2BCatalogBundle:Category', 'category')
            ->orderBy('category.id');

        $terms = [$this->getCategoryVisibilityResolvedTerm($qb, $configValue)];
        if ($account->getGroup()) {
            $terms[] = $this->getAccountGroupCategoryVisibilityResolvedTerm($qb, $account->getGroup(), $configValue);
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

    /**
     * @param array $categoryIds
     * @param int $visibility
     * @param Account $account
     */
    public function updateAccountCategoryVisibilityByCategory(Account $account, array $categoryIds, $visibility)
    {
        if (!$categoryIds) {
            return;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved', 'acvr')
            ->set('acvr.visibility', $visibility)
            ->where($qb->expr()->eq('acvr.account', ':account'))
            ->andWhere($qb->expr()->in('IDENTITY(acvr.category)', ':categoryIds'))
            ->setParameters(['account' => $account, 'categoryIds' => $categoryIds]);

        $qb->getQuery()->execute();
    }

    /**
     * @param Category $category
     * @param Account $account
     * @return null|AccountCategoryVisibilityResolved
     */
    public function findByPrimaryKey(Category $category, Account $account)
    {
        return $this->findOneBy(['account' => $account, 'category' => $category]);
    }
}
