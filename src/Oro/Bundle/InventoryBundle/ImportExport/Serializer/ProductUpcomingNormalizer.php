<?php

namespace Oro\Bundle\InventoryBundle\ImportExport\Serializer;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;

/**
 * Transforms scalar value from csv file to fallback value.
 */
class ProductUpcomingNormalizer implements ContextAwareDenormalizerInterface
{
    protected UpcomingProductProvider $productUpcomingProvider;

    public function __construct(UpcomingProductProvider $productUpcomingProvider)
    {
        $this->productUpcomingProvider = $productUpcomingProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return is_a($type, EntityFieldFallbackValue::class, true) &&
            $context['entityName'] === Product::class &&
            !empty($context['fieldName']) &&
            $context['fieldName'] === UpcomingProductProvider::IS_UPCOMING;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        if ($data === '1') {
            $fallbackEntity = new EntityFieldFallbackValue();
            $fallbackEntity->setScalarValue(1);
        } else {
            $fallbackEntity = null;
        }

        return $fallbackEntity;
    }
}
