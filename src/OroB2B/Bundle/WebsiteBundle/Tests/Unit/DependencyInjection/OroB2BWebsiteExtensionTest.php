<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use OroB2B\Bundle\WebsiteBundle\DependencyInjection\OroB2BWebsiteExtension;

class OroB2BWebsiteExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroB2BWebsiteExtension());

        $expectedParameters = [
            'orob2b_website.entity.website.class'
        ];

        $this->assertParametersLoaded($expectedParameters);
        $this->assertExtensionConfigsLoaded([OroB2BWebsiteExtension::ALIAS]);
    }

    public function testGetAlias()
    {
        $extension = new OroB2BWebsiteExtension();
        $this->assertEquals(OroB2BWebsiteExtension::ALIAS, $extension->getAlias());
    }
}
