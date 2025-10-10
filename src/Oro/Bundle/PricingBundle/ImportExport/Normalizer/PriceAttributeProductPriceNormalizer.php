<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;

/**
 * Normalizes PriceAttributeProductPrice entities for export by excluding quantity data
 */
class PriceAttributeProductPriceNormalizer extends ConfigurableEntityNormalizer
{
    /**
     * @param PriceAttributeProductPrice $object
     *
     */
    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
        $data = parent::normalize($object, $format, $context);

        if (array_key_exists('quantity', $data)) {
            unset($data['quantity']);
        }

        return $data;
    }

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof PriceAttributeProductPrice;
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return false;
    }
}
