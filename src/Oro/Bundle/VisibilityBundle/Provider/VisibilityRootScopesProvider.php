<?php

namespace Oro\Bundle\VisibilityBundle\Provider;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

/**
 * In community edition we have only one root scope - default scope
 */
class VisibilityRootScopesProvider implements VisibilityRootScopesProviderInterface
{
    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @return Scope[]
     */
    public function getScopes()
    {
        return [$this->scopeManager->findDefaultScope()];
    }
}
