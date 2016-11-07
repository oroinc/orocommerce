<?php

namespace Oro\Bundle\VisibilityBundle\Provider;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

class VisibilityRootScopesProvider implements VisibilityRootScopesProviderInterface
{
    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @param ScopeManager $scopeManager
     */
    public function __construct(ScopeManager $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    /**
     * @return Scope[]
     */
    public function getScopes()
    {
        return [$this->scopeManager->findDefaultScope()];
    }
}
