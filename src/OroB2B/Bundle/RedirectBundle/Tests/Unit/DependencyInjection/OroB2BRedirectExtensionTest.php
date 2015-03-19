<?php

namespace OroB2B\Bundle\RedirectBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\RedirectBundle\DependencyInjection\OroB2BRedirectExtension;

class OroB2BRedirectExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroB2BRedirectExtension());

        $expectedParameters = [
            'orob2b_redirect.slug.class',
        ];
        $this->assertParametersLoaded($expectedParameters);
    }
}
