<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\PayPalBundle\DependencyInjection\OroPayPalExtension;

class OroPayPalExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroPayPalExtension());

        $expectedDefinitions = [
            'oro_paypal.payment_method.payflow_gateway.config',
            'oro_paypal.event_listener.callback.payflow'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
