<?php

namespace Oro\Bundle\FedexShippingBundle\Transformer;

/**
 * Defines the contract for transforming between FedEx and shipping system units.
 *
 * Implementations provide bidirectional conversion between FedEx unit values
 * and the shipping system's unit representations.
 */
interface FedexToShippingUnitTransformerInterface
{
    public function transform(string $fedexValue): string;

    public function reverseTransform(string $shippingValue): string;
}
