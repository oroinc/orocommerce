<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PaymentBundle\DependencyInjection\OroPaymentExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroPaymentExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroPaymentExtension());

        $expectedDefinitions = [
            'oro_payment.formatter.payment_method_label',
            'oro_payment.twig.payment_method_extension',
            'oro_payment.context.doctrine_line_item_collection_factory',
            'oro_payment.context.builder_factory_basic',
            'oro_payment.line_item.builder_factory_basic',
            'oro_payment.context.rules_converter_basic',
            'oro_payment.expression_language.decorated_product_line_item_factory',
            'oro_payment.datagrid.payment_rule_actions_visibility_provider',
            'oro_payment.mass_action.status.enable',
            'oro_payment.mass_action.status.disable',
            'oro_payment.mass_action.status_handler',
            'oro_payment.payment_method.composite_provider',
            'oro_payment.payment_method_view.composite_provider',
            'oro_payment.action.capture_payment_transaction',
            'oro_payment.condition.payment_transaction_was_charged',
            'oro_payment.rule_filtration.basic_service',
            'oro_payment.provider.methods_configs_rules.by_context.basic',
            'oro_payment.enabled_rule_filtration.basic_service',
            'oro_payment.provider.methods_configs_rules.by_context_required_parameters',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
