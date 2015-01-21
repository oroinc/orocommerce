<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use OroB2B\Bundle\AttributeBundle\DependencyInjection\OroB2BAttributeExtension;

class OroB2BAttributeExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroB2BAttributeExtension());

        $expectedParameters = [
            'orob2b_attribute.attribute.class'
        ];

        $this->assertParametersLoaded($expectedParameters);
    }
}
