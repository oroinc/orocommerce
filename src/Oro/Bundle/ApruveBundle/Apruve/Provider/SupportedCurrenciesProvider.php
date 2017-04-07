<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Provider;

class SupportedCurrenciesProvider implements SupportedCurrenciesProviderInterface
{
    /**
     * Apruve supports only USD for now.
     */
    const USD = 'USD';

    /**
     * {@inheritdoc}
     */
    public function getCurrencies()
    {
        return [self::USD];
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported($currency)
    {
        return in_array($currency, $this->getCurrencies(), true);
    }
}
