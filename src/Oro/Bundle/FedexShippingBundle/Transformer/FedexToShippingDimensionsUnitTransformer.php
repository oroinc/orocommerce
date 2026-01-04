<?php

namespace Oro\Bundle\FedexShippingBundle\Transformer;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

class FedexToShippingDimensionsUnitTransformer implements FedexToShippingUnitTransformerInterface
{
    public const SHIPPING_DIMENSION_CM = 'cm';
    public const SHIPPING_DIMENSION_INCH = 'inch';

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
