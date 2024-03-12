<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Interface for sidebar form element providers for prices debug sidebars.
 */
interface SidebarFormProviderInterface
{
    public function getIndexPageSidebarFormElements(): array;
    public function getViewPageSidebarFormElements(?Product $product): array;
}
