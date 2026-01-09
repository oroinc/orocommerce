<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository;

use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;

/**
 * Defines the contract for repositories that manage visibility entities.
 *
 * Repositories implementing this interface must provide methods to query visibility settings
 * based on scope criteria and target entities (products or categories).
 */
interface VisibilityRepositoryInterface
{
    /**
     * @param ScopeCriteria $criteria
     * @param object $target
     * @return VisibilityInterface[]
     */
    public function findByScopeCriteriaForTarget(ScopeCriteria $criteria, $target);
}
