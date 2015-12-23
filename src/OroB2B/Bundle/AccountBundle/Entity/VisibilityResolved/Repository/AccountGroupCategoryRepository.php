<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * Composite primary key fields order:
 *  - accountGroup
 *  - category
 */
class AccountGroupCategoryRepository extends EntityRepository
{
    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     * @return bool
     */
    public function isCategoryVisible(Category $category, AccountGroup $accountGroup)
    {
        $qb = $this->createQueryBuilder('accountGroupCategoryVisibilityResolved');
        $categoryVisibilityResolved = $qb->select('accountGroupCategoryVisibilityResolved.visibility')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('accountGroupCategoryVisibilityResolved.category', ':category'),
                    $qb->expr()->eq('accountGroupCategoryVisibilityResolved.accountGroup', ':accountGroup')
                )
            )
            ->setParameters([
                'category' => $category,
                'accountGroup' =>$accountGroup
            ])
            ->getQuery()
            ->getOneOrNullResult();

        return isset($categoryVisibilityResolved['visibility'])
            && $categoryVisibilityResolved['visibility'] === BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }

    /**
     * @param int $visibility
     * @param AccountGroup $accountGroup
     * @return array
     */
    public function getCategoryIdsByVisibility($visibility, AccountGroup $accountGroup)
    {
        $qb = $this->createQueryBuilder('accountGroupCategoryVisibilityResolved');
        $categoryVisibilityResolved = $qb->select('IDENTITY(accountGroupCategoryVisibilityResolved.category)')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('accountGroupCategoryVisibilityResolved.visibility', ':visibility'),
                    $qb->expr()->eq('accountGroupCategoryVisibilityResolved.accountGroup', ':accountGroup')
                )
            )
            ->setParameters([
                'visibility' => $visibility,
                'accountGroup' => $accountGroup
            ])
            ->getQuery()
            ->getArrayResult();

        return array_map('current', $categoryVisibilityResolved);
    }
}
