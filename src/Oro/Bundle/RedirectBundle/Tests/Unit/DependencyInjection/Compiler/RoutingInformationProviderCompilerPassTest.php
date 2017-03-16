<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\RedirectBundle\DependencyInjection\Compiler\RoutingInformationProviderCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\TaggedServicesCompilerPassCase;

class RoutingInformationProviderCompilerPassTest extends TaggedServicesCompilerPassCase
{
    public function testProcess()
    {
        $this->assertTaggedServicesRegistered(
            new RoutingInformationProviderCompilerPass(),
            RoutingInformationProviderCompilerPass::PROVIDER_REGISTRY,
            RoutingInformationProviderCompilerPass::TAG,
            'registerProvider'
        );
    }
}
