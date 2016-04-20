<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use OroB2B\Bundle\ShippingBundle\DependencyInjection\OroB2BShippingExtension;

class OroB2BShippingExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroB2BShippingExtension());
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroB2BShippingExtension();
        $this->assertEquals(OroB2BShippingExtension::ALIAS, $extension->getAlias());
    }
}
