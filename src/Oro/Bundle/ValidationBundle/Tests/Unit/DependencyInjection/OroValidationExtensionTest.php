<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\ValidationBundle\DependencyInjection\OroValidationExtension;

class OroValidationExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroValidationExtension());

        $expectedDefinitions = [
            'oro_validation.validator_constraints.not_blank_one_of'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroValidationExtension();
        $this->assertEquals(OroValidationExtension::ALIAS, $extension->getAlias());
    }
}
