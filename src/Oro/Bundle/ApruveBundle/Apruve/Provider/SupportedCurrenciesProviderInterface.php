<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Provider;

interface SupportedCurrenciesProviderInterface
{
    /**
     * @return string[]
     */
    public function getCurrencies();

    /**
     * @param string $currency
     *
     * @return bool
     */
    public function isSupported($currency);
}
