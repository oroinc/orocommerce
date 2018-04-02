<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\RedirectBundle\DependencyInjection\OroRedirectExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

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
