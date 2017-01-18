<?php

namespace Oro\Bundle\ShippingBundle\Method\Configuration;

use Oro\Bundle\CurrencyBundle\Entity\Price;

interface PreConfiguredShippingMethodConfigurationInterface
{
    /**
     * @return string|null
     */
    public function getShippingMethod();

    /**
     * @return string|null
     */
    public function getShippingMethodType();

    /**
     * @return Price|null
     */
    public function getShippingCost();
}
