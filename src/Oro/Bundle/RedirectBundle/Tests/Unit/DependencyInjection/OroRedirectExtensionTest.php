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
            'oro_redirect.url_cache_type',
            'oro_redirect.url_provider_type',
            'oro_redirect.url_storage_cache.split_deep',
        ];
        $this->assertParametersLoaded($expectedParameters);
    }
}
