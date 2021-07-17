<?php

namespace Oro\Bundle\FedexShippingBundle\Transformer;

interface FedexToShippingUnitTransformerInterface
{
    public function transform(string $fedexValue): string;

    public function reverseTransform(string $shippingValue): string;
}
