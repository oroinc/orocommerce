<?php

namespace Oro\Bundle\ShippingBundle\Provider;

interface IntegrationAwareShippingMethodInterface
{
    /**
     * @param ShippingContextAwareInterface $context
     * @param array $optionsByTypes
     * @return array
     */
    public function calculatePrices(ShippingContextAwareInterface $context, array $optionsByTypes);
}
