<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\RedirectBundle\DependencyInjection\Compiler\ContextUrlProviderCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\AssertTaggedServicesCompilerPass;

class ContextUrlProviderCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $assertTaggedServicesCompilerPass = new AssertTaggedServicesCompilerPass();
        $assertTaggedServicesCompilerPass->assertTaggedServicesRegistered(
            new ContextUrlProviderCompilerPass(),
            ContextUrlProviderCompilerPass::PROVIDER_REGISTRY,
            ContextUrlProviderCompilerPass::TAG,
            'registerProvider'
        );
    }
}
