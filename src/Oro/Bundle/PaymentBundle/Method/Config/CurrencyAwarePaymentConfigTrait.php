<?php

namespace Oro\Bundle\PaymentBundle\Method\Config;

trait CurrencyAwarePaymentConfigTrait
{
    /**
     * @return array
     */
    abstract public function getAllowedCurrencies();

    /**
     * @param array $context
     * @return bool
     */
    public function isCurrencyApplicable(array $context = [])
    {
        $currencies = $this->getAllowedCurrencies();
        if ($currencies && !empty($context['currency'])) {
            return in_array($context['currency'], $currencies, true);
        }
        return false;
    }
}
