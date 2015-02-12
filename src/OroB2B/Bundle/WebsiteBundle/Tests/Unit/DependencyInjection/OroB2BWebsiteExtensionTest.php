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
            'orob2b_website.website.class'
        ];

        $this->assertParametersLoaded($expectedParameters);
    }
}
