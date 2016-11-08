<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

abstract class AbstractVisibilityRepository extends EntityRepository
{
    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertExecutor;

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

    /**
     * @param ScopeManager $scopeManager
     */
    public function setScopeManager($scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param InsertFromSelectQueryExecutor $insertExecutor
     */
    public function setInsertExecutor($insertExecutor)
    {
        $this->insertExecutor = $insertExecutor;
    }
}
