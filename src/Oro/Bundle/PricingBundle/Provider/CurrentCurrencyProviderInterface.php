<?php

namespace Oro\Bundle\PricingBundle\Provider;

/**
 * The interface for classes that can provider a current currency.
 */
interface CurrentCurrencyProviderInterface
{
    /**
     * Gets a current currency.
     */
    public function getCurrentCurrency(): ?string;
}
