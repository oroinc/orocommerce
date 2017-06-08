<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Host;

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
