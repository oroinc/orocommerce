<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\ContentVariantTypeCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\TaggedServicesCompilerPassCase;

class ContentVariantTypeCompilerPassTest extends TaggedServicesCompilerPassCase
{
    public function testProcess()
    {
        $this->assertTaggedServicesRegistered(
            new ContentVariantTypeCompilerPass(),
            ContentVariantTypeCompilerPass::REGISTRY_SERVICE,
            ContentVariantTypeCompilerPass::TAG,
            'addContentVariantType'
        );
    }
}
