<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\AbstractPrependExtensionTest;
use Oro\Bundle\CustomerBundle\DependencyInjection\OroCustomerExtension;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroCustomerExtensionTest extends AbstractPrependExtensionTest
{
    /**
     * Test Extension
     */
    public function testExtension()
    {
        $extension = new OroCustomerExtension();

        $this->loadExtension($extension);

        $expectedParameters = [
            'oro_customer.entity.customer.class',
            'oro_customer.entity.customer_group.class'
        ];

        $this->assertParametersLoaded($expectedParameters);

        $this->assertEquals('oro_customer', $extension->getAlias());
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $this->assertEquals(OroCustomerExtension::ALIAS, $this->getExtension()->getAlias());
    }

    /**
     * @return Extension
     */
    protected function getExtension()
    {
        return new OroCustomerExtension();
    }
}
