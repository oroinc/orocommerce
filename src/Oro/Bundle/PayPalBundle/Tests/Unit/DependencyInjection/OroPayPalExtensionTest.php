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
            'oro_paypal.event_listener.callback.payflow',
            'oro_paypal.integation.payflow_gateway.channel',
            'oro_paypal.integation.payments_pro.channel',
            'oro_paypal.integration.payflow_gateway.transport',
            'oro_paypal.integration.payments_pro.transport',
            'oro_paypal.method.config.provider.payments_pro.credit_card',
            'oro_paypal.method.config.provider.payments_pro.express_checkout',
            'oro_paypal.method.config.provider.payflow_gateway.credit_card',
            'oro_paypal.method.config.provider.payflow_gateway.express_checkout',
            'oro_paypal.method.config.factory.payments_pro.credit_card',
            'oro_paypal.method.config.factory.payments_pro.express_checkout',
            'oro_paypal.method.config.factory.payflow_gateway.credit_card',
            'oro_paypal.method.config.factory.payflow_gateway.express_checkout',
            'oro_paypal.method.generator.identifier.payments_pro.credit_card',
            'oro_paypal.method.generator.identifier.payments_pro.express_checkout',
            'oro_paypal.method.generator.identifier.payflow_gateway.credit_card',
            'oro_paypal.method.generator.identifier.payflow_gateway.express_checkout',
            'oro_paypal.method.view.provider.payments_pro.credit_card',
            'oro_paypal.method.view.provider.payments_pro.express_checkout',
            'oro_paypal.method.view.provider.payflow_gateway.credit_card',
            'oro_paypal.method.view.provider.payflow_gateway.express_checkout',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
