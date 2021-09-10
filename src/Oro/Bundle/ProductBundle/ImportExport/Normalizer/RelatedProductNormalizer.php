<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;

/**
 * Supports only denormalization and converts data from custom CSV format to the format
 * which supported by ConfigurableEntityNormalizer.
 */
class RelatedProductNormalizer extends ConfigurableEntityNormalizer
{
    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return is_a($type, RelatedProduct::class, true);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        if (!isset($data['sku'], $data['relatedItem'])) {
            return null;
        }

        return parent::denormalize(
            ['product' => ['sku' => $data['sku']], 'relatedItem' => ['sku' => $data['relatedItem']]],
            $type,
            $format,
            $context
        );
    }
}
