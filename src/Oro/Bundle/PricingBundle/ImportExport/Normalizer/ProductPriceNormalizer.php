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
    public function normalize($object, $format = null, array $context = array())
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
    public function supportsNormalization($data, $format = null, array $context = array())
    {
        return $data instanceof ProductPrice;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return false;
    }
}
