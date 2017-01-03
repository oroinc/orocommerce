<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;

interface MoneyOrderConfigInterface extends PaymentConfigInterface
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
