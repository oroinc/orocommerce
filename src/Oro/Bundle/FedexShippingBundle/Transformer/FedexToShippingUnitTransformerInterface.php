<?php

namespace Oro\Bundle\FedexShippingBundle\Transformer;

interface FedexToShippingUnitTransformerInterface
{
    /**
     * @param string $fedexValue
     *
     * @return string
     */
    public function transform(string $fedexValue): string;

    /**
     * @param string $shippingValue
     *
     * @return string
     */
    public function reverseTransform(string $shippingValue): string;
}
