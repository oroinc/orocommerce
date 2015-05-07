<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\CustomerAdminBundle\DependencyInjection\OroB2BCustomerAdminExtension;

/**
 * {@inheritdoc}
 */
class OroB2BCustomerAdminExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroB2BCustomerAdminExtension());

        $expectedParameters = ['orob2b_customer_admin.entity.customer.class'];
        $this->assertParametersLoaded($expectedParameters);
    }
}
