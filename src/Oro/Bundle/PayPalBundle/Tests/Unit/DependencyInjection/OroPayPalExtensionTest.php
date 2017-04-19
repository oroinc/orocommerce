<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PayPalBundle\DependencyInjection\OroPayPalExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroPayPalExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $container = $this->getContainerMock();
        $container->expects(static::once())
            ->method('getParameter')
            ->willReturn('prod')
        ;

        $extension = new OroPayPalExtension();
        $extension->load([], $container);

        $this->assertDefinitionsLoaded($this->getExpectedDefinitions());
    }

    public function testLoadForTestEnv()
    {
        $container = $this->getContainerMock();
        $container->expects(static::once())
            ->method('getParameter')
            ->willReturn('test')
        ;

        $extension = new OroPayPalExtension();
        $extension->load([], $container);

        $expectedDefinitions = array_merge(
            $this->getExpectedDefinitions(),
            $this->getExpectedTestDefinitions()
        );

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    /**
     * @return array
     */
    private function getExpectedDefinitions()
    {
        return [
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
            'oro_paypal.credit_card.method_view_factory_basic',
            'oro_paypal.express_checkout.method_view_factory_basic',
            'oro_paypal.event_listener.callback.payflow_gateway.credit_card',
            'oro_paypal.event_listener.callback.payments_pro.credit_card',
            'oro_paypal.event_listener.callback.payflow_gateway.express_checkout',
            'oro_paypal.event_listener.callback.payments_pro.express_checkout',
            'oro_paypal.event_listener.callback.payflow_gateway.express_checkout.redirect',
            'oro_paypal.event_listener.callback.payments_pro.express_checkout.redirect',
            'oro_paypal.event_listener.ip_check.payflow_gateway.credit_card',
            'oro_paypal.event_listener.ip_check.payments_pro.credit_card',
            'oro_paypal.settings.payment_action.provider',
            'oro_paypal.settings.card_type.provider',
        ];
    }

    /**
     * @return array
     */
    private function getExpectedTestDefinitions()
    {
        return [
            'oro_paypal.test.payment_method.express_checkout_provider',
            'oro_paypal.test.payment_method.view.express_checkout_provider',
        ];
    }
}
