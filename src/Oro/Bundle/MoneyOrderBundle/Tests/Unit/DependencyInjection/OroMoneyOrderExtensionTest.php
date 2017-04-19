<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MoneyOrderBundle\DependencyInjection\OroMoneyOrderExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroMoneyOrderExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroMoneyOrderExtension());

        $expectedDefinitions = [
            'oro_money_order.payment_method_provider.money_order',
            'oro_money_order.payment_method_view_provider.money_order',
            'oro_money_order.integration.channel',
            'oro_money_order.integration.transport',
            'oro_money_order.payment_method.config.provider',
            'oro_money_order.factory.money_order_config',
            'oro_money_order.generator.money_order_config_identifier',
            'oro_money_order.factory.method.money_order',
            'oro_money_order.factory.method_view.money_order',
            'oro_money_order.repository.money_settings',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $expectedParameters = [
            'oro_money_order.method.identifier_prefix.money_order'
        ];
        $this->assertParametersLoaded($expectedParameters);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroMoneyOrderExtension();
        $this->assertEquals(OroMoneyOrderExtension::ALIAS, $extension->getAlias());
    }
}
