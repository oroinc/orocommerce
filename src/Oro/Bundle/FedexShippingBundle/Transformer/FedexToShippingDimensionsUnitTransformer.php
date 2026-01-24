<?php

namespace Oro\Bundle\FedexShippingBundle\Transformer;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

/**
 * Transforms dimension units between FedEx and shipping system formats.
 *
 * Converts between FedEx dimension units (IN for inches, CM for centimeters)
 * and the shipping system's dimension representations (inch, cm).
 */
class FedexToShippingDimensionsUnitTransformer implements FedexToShippingUnitTransformerInterface
{
    const SHIPPING_DIMENSION_CM = 'cm';
    const SHIPPING_DIMENSION_INCH = 'inch';

    #[\Override]
    public function transform(string $fedexValue): string
    {
        if ($fedexValue === FedexIntegrationSettings::DIMENSION_IN) {
            return self::SHIPPING_DIMENSION_INCH;
        }

        return self::SHIPPING_DIMENSION_CM;
    }

    #[\Override]
    public function reverseTransform(string $shippingValue): string
    {
        if ($shippingValue === self::SHIPPING_DIMENSION_INCH) {
            return FedexIntegrationSettings::DIMENSION_IN;
        }

        return FedexIntegrationSettings::DIMENSION_CM;
    }
}
