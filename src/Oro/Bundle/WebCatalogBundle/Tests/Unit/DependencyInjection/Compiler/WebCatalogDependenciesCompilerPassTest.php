<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\WebCatalogDependenciesCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WebCatalogDependenciesCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var WebCatalogDependenciesCompilerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new WebCatalogDependenciesCompilerPass();
    }

    public function testProcessNoDefinition()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $providerDef = $container->register('oro_product.provider.content_variant_segment_provider');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['setWebCatalogUsageProvider', [new Reference('oro_web_catalog.provider.web_catalog_usage_provider')]]
            ],
            $providerDef->getMethodCalls()
        );
    }
}
