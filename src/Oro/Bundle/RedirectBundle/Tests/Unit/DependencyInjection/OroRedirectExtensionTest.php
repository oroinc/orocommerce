<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\RedirectBundle\DependencyInjection\OroRedirectExtension;

class OroRedirectExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroRedirectExtension());

        $expectedParameters = [
            'oro_redirect.entity.slug.class',
        ];
        $this->assertParametersLoaded($expectedParameters);
    }
}
