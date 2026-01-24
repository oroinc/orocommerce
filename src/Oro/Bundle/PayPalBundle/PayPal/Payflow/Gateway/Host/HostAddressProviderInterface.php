<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Host;

/**
 * Provides PayPal Payflow Gateway host addresses and form action URLs.
 *
 * Returns appropriate host addresses and form action URLs based on test/production mode.
 */
interface HostAddressProviderInterface
{
    /**
     * @param bool $testMode
     * @return string
     */
    public function getHostAddress($testMode);

    /**
     * @param bool $testMode
     * @return string
     */
    public function getFormAction($testMode);
}
