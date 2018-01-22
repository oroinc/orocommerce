<?php

namespace Oro\Bundle\FedexShippingBundle\Transformer;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

class FedexToShippingDimensionsUnitTransformer implements FedexToShippingUnitTransformerInterface
{
    const SHIPPING_DIMENSION_CM = 'cm';
    const SHIPPING_DIMENSION_INCH = 'inch';

    /**
     * {@inheritDoc}
     */
    public function transform(string $fedexValue): string
    {
        if ($fedexValue === FedexIntegrationSettings::DIMENSION_IN) {
            return self::SHIPPING_DIMENSION_INCH;
        }

        return self::SHIPPING_DIMENSION_CM;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform(string $shippingValue): string
    {
        if ($shippingValue === self::SHIPPING_DIMENSION_INCH) {
            return FedexIntegrationSettings::DIMENSION_IN;
        }

        return FedexIntegrationSettings::DIMENSION_CM;
    }
}
