<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\CustomerBundle\DependencyInjection\OroB2BCustomerExtension;

class OroB2BCustomerExtensionTest extends ExtensionTestCase
{
    /**
     * Test Extension
     */
    public function testExtension()
    {
        $extension = new OroB2BCustomerExtension();

        $this->loadExtension($extension);

        $expectedParameters = [
            'orob2b_customer.entity.customer.class',
            'orob2b_customer.entity.customer_group.class'
        ];

        $this->assertParametersLoaded($expectedParameters);

        $this->assertEquals('oro_b2b_customer', $extension->getAlias());
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroB2BCustomerExtension();

        $this->assertEquals(OroB2BCustomerExtension::ALIAS, $extension->getAlias());
    }
}
