<?php

namespace Oro\Bundle\PayPalBundle\Method\Config;

use OroB2B\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use OroB2B\Bundle\PaymentBundle\Method\Config\CountryConfigAwareInterface;
use OroB2B\Bundle\PaymentBundle\Method\Config\CurrencyConfigAwareInterface;

interface PayflowGatewayConfigInterface extends
    PaymentConfigInterface,
    CountryConfigAwareInterface,
    CurrencyConfigAwareInterface,
    PayflowConfigInterface
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
