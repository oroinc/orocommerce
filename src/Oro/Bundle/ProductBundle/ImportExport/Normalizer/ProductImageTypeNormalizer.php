<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;

/**
 * Normalizes/denormalizes product image type.
 */
class ProductImageTypeNormalizer extends ConfigurableEntityNormalizer
{
    protected string $productImageTypeClass;

    public function setProductImageTypeClass(string $productImageTypeClass)
    {
        $this->productImageTypeClass = $productImageTypeClass;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        return new ProductImageType($data);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return is_a($type, $this->productImageTypeClass, true);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return is_a($data, $this->productImageTypeClass);
    }
}
