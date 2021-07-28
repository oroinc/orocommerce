<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;

class ProductPriceNormalizer extends ConfigurableEntityNormalizer
{
    /**
     * @param ProductPrice $object
     *
     * {@inheritDoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $data = parent::normalize($object, $format, $context);

        if (array_key_exists('priceList', $data)) {
            unset($data['priceList']);
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof ProductPrice;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return false;
    }
}
