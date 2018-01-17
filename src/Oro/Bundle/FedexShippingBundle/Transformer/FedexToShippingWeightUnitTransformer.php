<?php

namespace Oro\Bundle\FedexShippingBundle\Transformer;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

class FedexToShippingWeightUnitTransformer implements FedexToShippingUnitTransformerInterface
{
    const SHIPPING_WEIGHT_KG = 'kg';
    const SHIPPING_WEIGHT_LBS = 'lbs';

    /**
     * {@inheritDoc}
     */
    public function transform(string $fedexValue): string
    {
        if ($fedexValue === FedexIntegrationSettings::UNIT_OF_WEIGHT_LB) {
            return self::SHIPPING_WEIGHT_LBS;
        }

        return self::SHIPPING_WEIGHT_KG;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform(string $shippingValue): string
    {
        if ($shippingValue === self::SHIPPING_WEIGHT_LBS) {
            return FedexIntegrationSettings::UNIT_OF_WEIGHT_LB;
        }

        return FedexIntegrationSettings::UNIT_OF_WEIGHT_KG;
    }
}
