<?php

namespace Oro\Bundle\PayPalBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;

/**
 * Defines the contract for PayPal payment method configuration.
 *
 * Extends payment configuration with PayPal-specific settings including purchase action,
 * test mode, and credentials.
 */
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
