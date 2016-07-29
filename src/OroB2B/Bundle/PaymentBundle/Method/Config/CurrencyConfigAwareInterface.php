<?php

namespace OroB2B\Bundle\PaymentBundle\Method\Config;

interface CurrencyConfigAwareInterface
{
    /**
     * @return array
     */
    public function getAllowedCurrencies();

    /**
     * @param array $context
     * @return bool
     */
    public function isCurrencyApplicable(array $context = []);
}
