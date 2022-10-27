<?php

namespace Oro\Bundle\VisibilityBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

/**
 * Provides scopes related to the product visibility scope type
 */
class VisibilityRootScopesProvider implements VisibilityRootScopesProviderInterface
{
    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    public function __construct(ScopeManager $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param Product $product
     * @return Scope[]
     */
    public function getScopes(Product $product = null)
    {
        return [$this->scopeManager->findDefaultScope()];
    }
}
