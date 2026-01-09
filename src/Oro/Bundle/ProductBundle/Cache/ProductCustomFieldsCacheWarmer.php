<?php

namespace Oro\Bundle\ProductBundle\Cache;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

/**
 * Warms cache of product custom extended fields.
 */
class ProductCustomFieldsCacheWarmer extends CacheWarmer
{
    /** @var CustomFieldProvider */
    private $customFieldProvider;

    public function __construct(CustomFieldProvider $customFieldProvider)
    {
        $this->customFieldProvider = $customFieldProvider;
    }

    #[\Override]
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $this->customFieldProvider->getEntityCustomFields(Product::class);
        return [];
    }

    #[\Override]
    public function isOptional(): bool
    {
        return true;
    }
}
