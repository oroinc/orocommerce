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
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
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
