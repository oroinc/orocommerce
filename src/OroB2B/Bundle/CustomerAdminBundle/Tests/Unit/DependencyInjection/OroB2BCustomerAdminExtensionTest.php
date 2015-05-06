<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\CustomerAdminBundle\DependencyInjection\OroB2BCustomerAdminExtension;

class OroB2BCustomerAdminExtensionTest extends ExtensionTestCase
{
    /**
     * Test Extension
     */
    public function testExtension()
    {
        $extension = new OroB2BCustomerAdminExtension();

        $this->loadExtension($extension);

        $expectedParameters = [
            'orob2b_customer_admin.customer.class',
            'orob2b_customer_admin.customer_group.class'
        ];

        $this->assertParametersLoaded($expectedParameters);

        $this->assertEquals('oro_b2b_customer_admin', $extension->getAlias());
    }
}
