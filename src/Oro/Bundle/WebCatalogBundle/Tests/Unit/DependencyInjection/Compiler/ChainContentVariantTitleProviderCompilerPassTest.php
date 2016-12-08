<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\ChainContentVariantTitleProviderCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\AssertTaggedServicesCompilerPass;

class ChainContentVariantTitleProviderCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $assertTaggedServicesCompilerPass = new AssertTaggedServicesCompilerPass();
        $assertTaggedServicesCompilerPass->assertTaggedServicesRegistered(
            new ChainContentVariantTitleProviderCompilerPass(),
            ChainContentVariantTitleProviderCompilerPass::CONTENT_VARIANT_PROVIDER,
            ChainContentVariantTitleProviderCompilerPass::CONTENT_VARIANT_PROVIDER_TAG,
            'addProvider'
        );
    }
}
