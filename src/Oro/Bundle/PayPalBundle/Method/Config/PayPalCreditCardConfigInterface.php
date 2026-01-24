<?php

namespace Oro\Bundle\PayPalBundle\Method\Config;

/**
 * Defines the contract for PayPal Credit Card payment method configuration.
 *
 * Extends base PayPal configuration with credit card-specific methods for accessing
 * zero-amount authorization, proxy, SSL verification, and CVV settings.
 */
interface PayPalCreditCardConfigInterface extends PayPalConfigInterface
{
    /**
     * @return bool
     */
    public function isZeroAmountAuthorizationEnabled();

    /**
     * @return bool
     */
    public function isAuthorizationForRequiredAmountEnabled();

    /**
     * @return array
     */
    public function getAllowedCreditCards();

    /**
     * @return bool
     */
    public function isDebugModeEnabled();

    /**
     * @return bool
     */
    public function isUseProxyEnabled();

    /**
     * @return string
     */
    public function getProxyHost();

    /**
     * @return int
     */
    public function getProxyPort();

    /**
     * @return bool
     */
    public function isSslVerificationEnabled();

    /**
     * @return bool
     */
    public function isRequireCvvEntryEnabled();
}
