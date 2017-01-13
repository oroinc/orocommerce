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
            'oro_paypal.event_listener.callback.payflow',
            'oro_paypal.integation.payflow_gateway.channel',
            'oro_paypal.integation.payments_pro.channel',
            'oro_paypal.integration.payflow_gateway.transport',
            'oro_paypal.integration.payments_pro.transport',
            'oro_paypal.method.view.provider.payments_pro_credit_card',
            'oro_paypal.method.view.provider.payflow_gateway_credit_card',
            'oro_paypal.method.view.provider.payments_pro_express_checkout',
            'oro_paypal.method.view.provider.payflow_gateway_express_checkout',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
