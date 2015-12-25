<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryRepository extends EntityRepository
{
    use CategoryVisibilityResolvedTermTrait;

    /**
     * @param Category $category
     * @param int $configValue
     * @return bool
     */
    public function isCategoryVisible(Category $category, $configValue)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('COALESCE(cvr.visibility, '. $qb->expr()->literal($configValue).')')
            ->from('OroB2BCatalogBundle:Category', 'category')
            ->leftJoin(
                'OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved',
                'cvr',
                Join::WITH,
                $qb->expr()->eq('cvr.category', 'category')
            )
            ->where($qb->expr()->eq('category', ':category'))
            ->setParameter('category', $category);

        $visibility = $qb->getQuery()->getSingleScalarResult();

        return (int)$visibility === BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }

    /**
     * @param int $visibility
     * @param int $configValue
     * @return array
     */
    public function getCategoryIdsByVisibility($visibility, $configValue)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('category.id')
            ->from('OroB2BCatalogBundle:Category', 'category')
            ->orderBy('category.id');

        $terms =  [$this->getCategoryVisibilityResolvedTerm($qb, $configValue)];

        if ($visibility === BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE) {
            $qb->andWhere($qb->expr()->gt(implode(' + ', $terms), 0));
        } else {
            $qb->andWhere($qb->expr()->lte(implode(' + ', $terms), 0));
        }

        $categoryVisibilityResolved = $qb->getQuery()->getArrayResult();

        return array_map('current', $categoryVisibilityResolved);
    }
}
