<?php

namespace OroB2B\Bundle\UserBundle\Tests\Unit;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\UserBundle\DependencyInjection\OroB2BUserExtension;

class OroB2BUserExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroB2BUserExtension());

        $expectedParameters = [];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
