<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SEOBundle\DependencyInjection\Compiler\UrlItemsProviderCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\TaggedServicesCompilerPassCase;

class UrlItemsProviderCompilerPassTest extends TaggedServicesCompilerPassCase
{
    public function testProcess()
    {
        $this->assertTaggedServicesRegistered(
            new UrlItemsProviderCompilerPass(),
            UrlItemsProviderCompilerPass::PROVIDER_REGISTRY,
            UrlItemsProviderCompilerPass::TAG,
            'addProvider'
        );
    }
}
