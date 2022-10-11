<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\SchemaOrgProductDescriptionProviderInterface;

/**
 * Provides to Schema.org product description to layouts.
 */
class SchemaOrgProductDescriptionLayoutDataProvider
{
    private SchemaOrgProductDescriptionProviderInterface $provider;

    public function __construct(SchemaOrgProductDescriptionProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    public function getDescription(Product $product): string
    {
        return $this->provider->getDescription($product);
    }
}
