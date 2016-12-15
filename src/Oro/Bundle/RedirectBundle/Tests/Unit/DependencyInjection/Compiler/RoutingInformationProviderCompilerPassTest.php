<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\RedirectBundle\DependencyInjection\Compiler\RoutingInformationProviderCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\AssertTaggedServicesCompilerPass;

class RoutingInformationProviderCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $assertTaggedServicesCompilerPass = new AssertTaggedServicesCompilerPass();
        $assertTaggedServicesCompilerPass->assertTaggedServicesRegistered(
            new RoutingInformationProviderCompilerPass(),
            RoutingInformationProviderCompilerPass::PROVIDER_REGISTRY,
            RoutingInformationProviderCompilerPass::TAG,
            'registerProvider'
        );
    }
}
