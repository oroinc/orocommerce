<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Host;

class HostAddressProvider implements HostAddressProviderInterface
{
    public const PRODUCTION_HOST_ADDRESS = 'https://payflowpro.paypal.com';
    public const PILOT_HOST_ADDRESS = 'https://pilot-payflowpro.paypal.com';

    public const PRODUCTION_FORM_ACTION = 'https://payflowlink.paypal.com';
    public const PILOT_FORM_ACTION = 'https://pilot-payflowlink.paypal.com';

    #[\Override]
    public function getHostAddress($testMode)
    {
        return $testMode ? self::PILOT_HOST_ADDRESS : self::PRODUCTION_HOST_ADDRESS;
    }

    #[\Override]
    public function getFormAction($testMode)
    {
        return $testMode ? self::PILOT_FORM_ACTION : self::PRODUCTION_FORM_ACTION;
    }
}
