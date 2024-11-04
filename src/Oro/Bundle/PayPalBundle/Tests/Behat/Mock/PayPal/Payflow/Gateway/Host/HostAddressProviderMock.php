<?php

namespace Oro\Bundle\PayPalBundle\Tests\Behat\Mock\PayPal\Payflow\Gateway\Host;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Host\HostAddressProviderInterface;

class HostAddressProviderMock implements HostAddressProviderInterface
{
    const PAYPAL_FORM_ACTION_MOCK = '/paypal-out-redirect-mock';

    #[\Override]
    public function getHostAddress($testMode)
    {
        return '';
    }

    #[\Override]
    public function getFormAction($testMode)
    {
        return self::PAYPAL_FORM_ACTION_MOCK;
    }
}
