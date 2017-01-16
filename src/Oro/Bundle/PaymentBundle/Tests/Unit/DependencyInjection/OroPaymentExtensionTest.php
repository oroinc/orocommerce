<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PaymentBundle\DependencyInjection\OroPaymentExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroPaymentExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroPaymentExtension());

        $expectedParameters = [
            'oro_payment.entity.payment_transaction.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'oro_payment.formatter.payment_method_label',
            'oro_payment.twig.payment_method_extension',
            'oro_payment.context.doctrine_line_item_collection_factory',
            'oro_payment.context.builder_factory_basic',
            'oro_payment.line_item.builder_factory_basic',
            'oro_payment.context.rules_converter_basic',
            'oro_payment.payment_methods_configs.rules_provider_basic',
            'oro_payment.query_designer.select_query_converter',
            'oro_payment.expression_language.decorated_product_line_item_factory',
            'oro_payment.datagrid.payment_rule_actions_visibility_provider',
            'oro_paypal.config.provider.payments_pro_credit_card',
            'oro_paypal.config.provider.payments_pro_express_checkout',
            'oro_paypal.config.provider.payflow_gateway_credit_card',
            'oro_paypal.config.provider.payflow_gateway_express_checkout'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
