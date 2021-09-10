<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

/**
 * Abstract class for doctrine repository which contains common logic for repositories for Visibility entities
 */
class AbstractProductVisibilityRepository extends ServiceEntityRepository implements VisibilityRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findByScopeCriteriaForTarget(ScopeCriteria $criteria, $target)
    {
        if (!is_a($target, Product::class)) {
            throw new \InvalidArgumentException();
        }

        $qb = $this->createQueryBuilder('v');
        $qb->select('scope, v')
            ->join('v.scope', 'scope')
            ->where('v.product = :product')
            ->setParameter('product', $target);

        $criteria->applyWhere($qb, 'scope');

        return $qb->getQuery()->getResult();
    }
}
