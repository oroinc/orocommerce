<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;

interface SidebarFormProviderInterface
{
    public function getIndexPageSidebarFormElements(): array;
    public function getViewPageSidebarFormElements(?Product $product): array;
}
