<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SEOBundle\DependencyInjection\Compiler\FullListUrlProvidersCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

class FullListUrlProvidersCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var FullListUrlProvidersCompilerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new FullListUrlProvidersCompilerPass();
    }

    public function testProcessCompositeDoesNotExist()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcessNoTaggedServicesFound()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_seo.sitemap.provider.full_list_urls_provider_registry')
            ->addArgument([]);

        $this->compiler->process($container);

        self::assertSame([], $registryDef->getArgument(0));
    }

    public function testProcessWithTaggedServices()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_seo.sitemap.provider.full_list_urls_provider_registry')
            ->addArgument([]);

        $container->register('service.name.1')
            ->addTag('oro_seo.sitemap.url_items_provider', ['alias' => 'taggedService1Alias']);
        $container->register('service.name.2')
            ->addTag('oro_seo.sitemap.url_items_provider', ['alias' => 'taggedService2Alias']);

        $this->compiler->process($container);

        self::assertEquals(
            [
                'taggedService1Alias' => new Reference('service.name.1'),
                'taggedService2Alias' => new Reference('service.name.2')
            ],
            $registryDef->getArgument(0)
        );
    }

    public function testProcessWithTaggedServicesWithoutAlias()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Could not retrieve "alias" attribute for "service.name.1"');

        $container = new ContainerBuilder();
        $container->register('oro_seo.sitemap.provider.full_list_urls_provider_registry')
            ->addArgument([]);

        $container->register('service.name.1')
            ->addTag('oro_seo.sitemap.url_items_provider');

        $this->compiler->process($container);
    }
}
