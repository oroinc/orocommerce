<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\ContentVariantProviderCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\AssertTaggedServicesCompilerPass;

class ContentVariantProviderCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $assertTaggedServicesCompilerPass = new AssertTaggedServicesCompilerPass();
        $assertTaggedServicesCompilerPass->assertTaggedServicesRegistered(
            new ContentVariantProviderCompilerPass(),
            ContentVariantProviderCompilerPass::CONTENT_VARIANT_PROVIDER_REGISTRY,
            ContentVariantProviderCompilerPass::CONTENT_VARIANT_PROVIDER_TAG,
            'addContentVariantProvider'
        );
    }
}
