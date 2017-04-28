<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Provider;

class SupportedCurrenciesProvider implements SupportedCurrenciesProviderInterface
{
    /**
     * Apruve supports only USD for now.
     */
    const USD = 'USD';

    /**
     * {@inheritDoc}
     */
    public function getCurrencies()
    {
        return [self::USD];
    }

    /**
     * {@inheritDoc}
     */
    public function isSupported($currency)
    {
        return in_array($currency, $this->getCurrencies(), true);
    }
}
