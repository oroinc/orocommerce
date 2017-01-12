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
            'oro_paypal.payment_method.paypal_credit_card.config',
            'oro_paypal.payment_method.paypal_express_checkout.config',
            'oro_paypal.event_listener.callback.payflow'
            'oro_paypal.method.view.provider.credit_card',
            'oro_paypal.method.view.provider.express_checkout',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
