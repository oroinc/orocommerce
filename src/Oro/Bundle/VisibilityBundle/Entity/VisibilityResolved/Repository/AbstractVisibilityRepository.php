<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;

abstract class AbstractVisibilityRepository extends EntityRepository
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
