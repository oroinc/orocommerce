<?php

namespace Oro\Bundle\PayPalBundle\Tests\Behat\Mock\PayPal\Payflow\Client;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Client\ClientInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\ReturnUrl;

class NVPClientMock implements ClientInterface
{
    /** {@inheritdoc} */
    public function send($hostAddress, array $options = [], array $connectionOptions = [])
    {
        return [
            'RESULT' => '0',
            'RESPMSG' => 'Approved',
            'SECURETOKEN' => '8w0KDpDSXj0Wh9kLHh6VVfwiz',
            'SECURETOKENID' => '00ebe252-8910-45c1-8e89-32b2a74e800e',
            'PNREF' => array_key_exists(ReturnUrl::RETURNURL, $options) ? null : 1,
        ];
    }
}
