<?php

namespace Oro\Bundle\ProductBundle\Cache;

use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler\EnumVariantFieldValueHandler;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

/**
 * Warms cache of product`s extended Enum field values.
 */
class EnumProductVariantFieldValuesCacheWarmer extends CacheWarmer
{
    /** @var EntityWithFieldsProvider */
    private $entityWithFieldsProvider;

    /** @var EnumVariantFieldValueHandler */
    private $enumVariantFieldValueHandler;

    public function __construct(
        EntityWithFieldsProvider $entityWithFieldsProvider,
        EnumVariantFieldValueHandler $enumVariantFieldValueHandler
    ) {
        $this->entityWithFieldsProvider = $entityWithFieldsProvider;
        $this->enumVariantFieldValueHandler = $enumVariantFieldValueHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp(string $cacheDir): array
    {
        $fields = $this->entityWithFieldsProvider->getFieldsForEntity(Product::class);
        foreach ($fields as $field) {
            $relatedEntityName = $field['related_entity_name'] ?? null;
            if (!$relatedEntityName || !is_a($relatedEntityName, EnumOptionInterface::class, true)) {
                continue;
            }

            $this->enumVariantFieldValueHandler->getPossibleValues($field['name']);
        }
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional(): bool
    {
        return true;
    }
}
