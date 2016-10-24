<?php

namespace Oro\Bundle\VisibilityBundle\Provider;

use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * Visibility settings can be grouped by high level scopes
 */
interface VisibilityRootScopesProviderInterface
{
    /**
     * @return Scope[]
     */
    public function getScopes();
}
