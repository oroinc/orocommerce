<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\CMSBundle\DependencyInjection\OroB2BCMSExtension;

class OroB2BCMSExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroB2BCMSExtension());

        $expectedParameters = [
            'orob2b_cms.page.class',
        ];
        $this->assertParametersLoaded($expectedParameters);
    }
}
