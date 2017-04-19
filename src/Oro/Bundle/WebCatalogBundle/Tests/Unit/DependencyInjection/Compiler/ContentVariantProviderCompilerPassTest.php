<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\ContentVariantProviderCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\TaggedServicesCompilerPassCase;

class ContentVariantProviderCompilerPassTest extends TaggedServicesCompilerPassCase
{
    public function testProcess()
    {
        $this->assertTaggedServicesRegistered(
            new ContentVariantProviderCompilerPass(),
            ContentVariantProviderCompilerPass::CONTENT_VARIANT_PROVIDER_REGISTRY,
            ContentVariantProviderCompilerPass::CONTENT_VARIANT_PROVIDER_TAG,
            'addProvider'
        );
    }
}
