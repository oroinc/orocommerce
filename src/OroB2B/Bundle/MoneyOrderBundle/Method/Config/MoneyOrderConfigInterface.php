<?php

namespace OroB2B\Bundle\MoneyOrderBundle\Method\Config;

use OroB2B\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use OroB2B\Bundle\PaymentBundle\Method\Config\CountryConfigAwareInterface;
use OroB2B\Bundle\PaymentBundle\Method\Config\CurrencyConfigAwareInterface;

interface MoneyOrderConfigInterface extends
    PaymentConfigInterface,
    CurrencyConfigAwareInterface,
    CountryConfigAwareInterface
{
    /**
     * @return string
     */
    public function getPayTo();

    /**
     * @return string
     */
    public function getSendTo();
}
