<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\RedirectBundle\DependencyInjection\Compiler\ContextUrlProviderCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\TaggedServicesCompilerPassCase;

class ContextUrlProviderCompilerPassTest extends TaggedServicesCompilerPassCase
{
    public function testProcess()
    {
        $this->assertTaggedServicesRegistered(
            new ContextUrlProviderCompilerPass(),
            ContextUrlProviderCompilerPass::PROVIDER_REGISTRY,
            ContextUrlProviderCompilerPass::TAG,
            'registerProvider'
        );
    }
}
