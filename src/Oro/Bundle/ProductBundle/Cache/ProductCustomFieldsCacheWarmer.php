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

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir): void
    {
        $this->customFieldProvider->getEntityCustomFields(Product::class);
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional(): bool
    {
        return true;
    }
}
