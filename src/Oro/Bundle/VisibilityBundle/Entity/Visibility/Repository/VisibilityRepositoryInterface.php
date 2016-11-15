<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository;

use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;

interface VisibilityRepositoryInterface
{
    /**
     * @param ScopeCriteria $criteria
     * @param object $target
     * @return VisibilityInterface[]
     */
    public function findByScopeCriteriaForTarget(ScopeCriteria $criteria, $target);
}
