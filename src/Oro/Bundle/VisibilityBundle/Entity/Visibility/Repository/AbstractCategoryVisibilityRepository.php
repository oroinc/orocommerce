<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class AbstractCategoryVisibilityRepository extends EntityRepository implements VisibilityRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findByScopeCriteriaForTarget(ScopeCriteria $criteria, $target)
    {
        if (!is_a($target, Category::class)) {
            throw new \InvalidArgumentException();
        }

        $qb = $this->createQueryBuilder('v');
        $qb->select('scope, v')
            ->join('v.scope', 'scope')
            ->where('v.category = :category')
            ->setParameter('category', $target);

        $criteria->applyWhere($qb, 'scope');

        return $qb->getQuery()->getResult();
    }
}
