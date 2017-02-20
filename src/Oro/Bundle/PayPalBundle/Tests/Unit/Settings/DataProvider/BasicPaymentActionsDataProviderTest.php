<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Settings\DataProvider;

use Oro\Bundle\PayPalBundle\Settings\DataProvider\BasicPaymentActionsDataProvider;

class BasicPaymentActionsDataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPaymentActions()
    {
        $provider = new BasicPaymentActionsDataProvider();

        $this->assertEquals([
            'authorize',
            'charge',
        ], $provider->getPaymentActions());
    }
}
