<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Oro\Bundle\PaymentBundle\Method\Config\CountryConfigAwareInterface;
use Oro\Bundle\PaymentBundle\Method\Config\CurrencyConfigAwareInterface;

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
