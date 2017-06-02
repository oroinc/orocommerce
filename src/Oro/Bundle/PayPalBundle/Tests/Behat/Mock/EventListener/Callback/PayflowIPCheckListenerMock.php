<?php

namespace Oro\Bundle\PayPalBundle\Tests\Behat\Mock\EventListener\Callback;

use Oro\Bundle\PayPalBundle\EventListener\Callback\PayflowIPCheckListener;

class PayflowIPCheckListenerMock extends PayflowIPCheckListener
{
    /**
     * @var string[]
     */
    protected $allowedIPs = [
        '0.0.0.0/0',
    ];
}
