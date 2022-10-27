<?php

namespace Oro\Bundle\VisibilityBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * Visibility settings can be grouped by high level scopes
 */
interface VisibilityRootScopesProviderInterface
{
    /**
     * @param Product|null $product
     *
     * @return Scope[]
     */
    public function getScopes(Product $product = null);
}
