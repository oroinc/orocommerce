<?php

namespace Oro\Bundle\ShippingBundle\Method\Configuration;

use Oro\Bundle\CurrencyBundle\Entity\Price;

/**
 * Defines the contract for pre-configured shipping method configurations.
 *
 * This interface provides access to pre-selected shipping method, method type, and cost, allowing entities
 * to specify default or required shipping options that can be used during checkout or order processing.
 */
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
