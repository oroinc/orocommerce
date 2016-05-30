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
        if (!empty($context['currency']) && $this->getAllowedCurrencies()) {
            return in_array($context['currency'], $this->getAllowedCurrencies(), true);
        }

        return false;
    }

    /**
     * @return array
     */
    abstract protected function getAllowedCurrencies();
}
