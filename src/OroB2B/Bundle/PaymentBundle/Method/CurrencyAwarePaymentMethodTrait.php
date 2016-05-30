<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

trait CurrencyAwarePaymentMethodTrait
{
    /**
     * @param array $context
     * @return bool
     */
    public function isCurrencyApplicable(array $context = [])
    {
        $currencies = $this->getAllowedCurrencies();
        if (!empty($context['currency']) && $currencies) {
            return in_array($context['currency'], $currencies, true);
        }

        return false;
    }

    /**
     * @return array
     */
    abstract protected function getAllowedCurrencies();
}
