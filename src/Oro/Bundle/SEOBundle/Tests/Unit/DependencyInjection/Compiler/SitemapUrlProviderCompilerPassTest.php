<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SEOBundle\DependencyInjection\Compiler\SitemapUrlProviderCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\AssertTaggedServicesCompilerPass;

class SitemapUrlProviderCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $assertTaggedServicesCompilerPass = new AssertTaggedServicesCompilerPass();
        $assertTaggedServicesCompilerPass->assertTaggedServicesRegistered(
            new SitemapUrlProviderCompilerPass(),
            SitemapUrlProviderCompilerPass::PROVIDER_REGISTRY,
            SitemapUrlProviderCompilerPass::TAG,
            'addProvider'
        );
    }
}
