<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\AccountBundle\DependencyInjection\OroB2BAccountExtension;

class OroB2BAccountExtensionTest extends ExtensionTestCase
{
    /**
     * Test Extension
     */
    public function testExtension()
    {
        $extension = new OroB2BAccountExtension();

        $this->loadExtension($extension);

        $expectedParameters = [
            'orob2b_account.entity.account.class',
            'orob2b_account.entity.account_group.class'
        ];

        $this->assertParametersLoaded($expectedParameters);

        $this->assertEquals('oro_b2b_account', $extension->getAlias());
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroB2BAccountExtension();

        $this->assertEquals(OroB2BAccountExtension::ALIAS, $extension->getAlias());
    }
}
