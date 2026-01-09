<?php

namespace Oro\Bundle\FedexShippingBundle\Transformer;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

/**
 * Transforms weight units between FedEx and shipping system formats.
 *
 * Converts between FedEx weight units (LB for pounds, KG for kilograms)
 * and the shipping system's weight representations (lbs, kg).
 */
class FedexToShippingWeightUnitTransformer implements FedexToShippingUnitTransformerInterface
{
    public const SHIPPING_WEIGHT_KG = 'kg';
    public const SHIPPING_WEIGHT_LBS = 'lbs';

    #[\Override]
    public function transform(string $fedexValue): string
    {
        if ($fedexValue === FedexIntegrationSettings::UNIT_OF_WEIGHT_LB) {
            return self::SHIPPING_WEIGHT_LBS;
        }

        return self::SHIPPING_WEIGHT_KG;
    }

    #[\Override]
    public function reverseTransform(string $shippingValue): string
    {
        if ($shippingValue === self::SHIPPING_WEIGHT_LBS) {
            return FedexIntegrationSettings::UNIT_OF_WEIGHT_LB;
        }

        return FedexIntegrationSettings::UNIT_OF_WEIGHT_KG;
    }
}
