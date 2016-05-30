<?php

namespace OroB2B\Bundle\MoneyOrderBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\OroB2BMoneyOrderExtension;

class OroB2BMoneyOrderExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroB2BMoneyOrderExtension());

        $expectedDefinitions = [
            'orob2b_money_order.payment_method.money_order',
            'orob2b_money_order.payment_method.view.money_order',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroB2BMoneyOrderExtension();
        $this->assertEquals(OroB2BMoneyOrderExtension::ALIAS, $extension->getAlias());
    }
}
