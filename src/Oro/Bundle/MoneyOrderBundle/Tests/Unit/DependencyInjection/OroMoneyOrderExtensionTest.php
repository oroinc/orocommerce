<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\MoneyOrderBundle\DependencyInjection\OroMoneyOrderExtension;

class OroMoneyOrderExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroMoneyOrderExtension());

        $expectedDefinitions = [
            'oro_money_order.payment_method.money_order',
            'oro_money_order.payment_method.view.money_order',
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
