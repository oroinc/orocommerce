<?php

namespace OroB2B\Bundle\EmailBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\EmailBundle\DependencyInjection\OroB2BEmailExtension;

class OroB2BEmailExtensionTest extends ExtensionTestCase
{
    /**
     * Test Extension
     */
    public function testExtension()
    {
        $this->loadExtension(new OroB2BEmailExtension());

        $expectedParameters = [
            'orob2b_email.email_template.entity.class',
            'orob2b_email.twig.string_loader.class',
            'orob2b_email.mailer.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'orob2b_email.twig.string_loader',
            'orob2b_email.mailer',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
