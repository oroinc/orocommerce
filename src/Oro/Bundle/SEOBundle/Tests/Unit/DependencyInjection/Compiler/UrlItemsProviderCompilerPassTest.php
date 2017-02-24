<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SEOBundle\DependencyInjection\Compiler\UrlItemsProviderCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\AssertTaggedServicesCompilerPass;

class UrlItemsProviderCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $assertTaggedServicesCompilerPass = new AssertTaggedServicesCompilerPass();
        $assertTaggedServicesCompilerPass->assertTaggedServicesRegistered(
            new UrlItemsProviderCompilerPass(),
            UrlItemsProviderCompilerPass::PROVIDER_REGISTRY,
            UrlItemsProviderCompilerPass::TAG,
            'addProvider'
        );
    }
}
