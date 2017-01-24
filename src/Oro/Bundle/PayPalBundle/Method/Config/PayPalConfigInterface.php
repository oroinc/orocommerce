<?php

namespace Oro\Bundle\PayPalBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;

interface PayPalConfigInterface extends PaymentConfigInterface
{
    /**
     * @return string
     */
    public function getPurchaseAction();

    /**
     * @return bool
     */
    public function isTestMode();

    /**
     * @return array
     */
    public function getCredentials();
}
