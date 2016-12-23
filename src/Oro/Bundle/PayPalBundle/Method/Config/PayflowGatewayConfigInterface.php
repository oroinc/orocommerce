<?php

namespace Oro\Bundle\PayPalBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;

interface PayflowGatewayConfigInterface extends
    PaymentConfigInterface,
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
