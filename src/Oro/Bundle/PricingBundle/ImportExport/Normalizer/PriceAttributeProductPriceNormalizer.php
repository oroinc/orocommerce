<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;

class PriceAttributeProductPriceNormalizer extends ConfigurableEntityNormalizer
{
    /**
     * @param PriceAttributeProductPrice $object
     *
     * {@inheritDoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $data = parent::normalize($object, $format, $context);

        if (array_key_exists('quantity', $data)) {
            unset($data['quantity']);
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null, array $context = array())
    {
        return $data instanceof PriceAttributeProductPrice;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return false;
    }
}
