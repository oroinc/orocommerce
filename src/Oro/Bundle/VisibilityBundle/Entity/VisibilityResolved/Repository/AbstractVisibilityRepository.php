<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * Abstract class for doctrine repository which contains common logic for repositories for VisibilityResolved entities
 */
abstract class AbstractVisibilityRepository extends ServiceEntityRepository
{
    /**
     * @param Scope $scope
     * @return int
     */
    public function clearTable(Scope $scope = null)
    {
        $qb = $this->createQueryBuilder('visibility_resolved')
            ->delete();

        if ($scope) {
            $qb->andWhere('visibility_resolved.scope = :scope')
                ->setParameter('scope', $scope);
        }

        return $qb->getQuery()
            ->execute();
    }
}
