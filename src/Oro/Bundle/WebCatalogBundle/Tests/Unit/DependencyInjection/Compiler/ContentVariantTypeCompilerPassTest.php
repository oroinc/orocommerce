<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\ContentVariantTypeCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\AssertTaggedServicesCompilerPass;

class ContentVariantTypeCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $assertTaggedServicesCompilerPass = new AssertTaggedServicesCompilerPass();
        $assertTaggedServicesCompilerPass->assertContainerConfigured(
            ContentVariantTypeCompilerPass::class,
            ContentVariantTypeCompilerPass::WEB_CATALOG_PAGE_TYPE_REGISTRY,
            ContentVariantTypeCompilerPass::WEB_CATALOG_PAGE_TYPE_TAG,
            'addContentVariantType'
        );
    }
}
