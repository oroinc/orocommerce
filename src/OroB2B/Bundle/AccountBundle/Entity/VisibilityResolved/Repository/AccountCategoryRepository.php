<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;

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
    /**
     * @param Category $category
     * @param Account $account
     * @return bool
     */
    public function isCategoryVisible(Category $category, Account $account)
    {
        $qb = $this->createQueryBuilder('accountGroupCategoryVisibilityResolved');
        $categoryVisibilityResolved = $qb->select('accountGroupCategoryVisibilityResolved.visibility')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('accountGroupCategoryVisibilityResolved.category', ':category'),
                    $qb->expr()->eq('accountGroupCategoryVisibilityResolved.account', ':account')
                )
            )
            ->setParameters([
                'category' => $category,
                'account' => $account
            ])
            ->getQuery()
            ->getOneOrNullResult();

        return isset($categoryVisibilityResolved['visibility'])
            && $categoryVisibilityResolved['visibility'] === BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }

    /**
     * @param int $visibility
     * @param Account $account
     * @return array
     */
    public function getCategoryIdsByVisibility($visibility, Account $account)
    {
        $qb = $this->createQueryBuilder('accountGroupCategoryVisibilityResolved');
        $categoryVisibilityResolved = $qb->select('IDENTITY(accountGroupCategoryVisibilityResolved.category)')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('accountGroupCategoryVisibilityResolved.visibility', ':visibility'),
                    $qb->expr()->eq('accountGroupCategoryVisibilityResolved.account', ':account')
                )
            )
            ->setParameters([
                'visibility' => $visibility,
                'account' => $account
            ])
            ->getQuery()
            ->getArrayResult();

        return array_map('current', $categoryVisibilityResolved);
    }
}
