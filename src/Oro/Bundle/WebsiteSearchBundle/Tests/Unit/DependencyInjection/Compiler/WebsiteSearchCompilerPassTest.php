<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler\WebsiteSearchCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WebsiteSearchCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var WebsiteSearchCompilerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new WebsiteSearchCompilerPass();
    }

    public function testProcessRegistryDoesNotExist()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcessNoTaggedServicesFound()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_website_search.placeholder.registry');

        $this->compiler->process($container);

        self::assertSame([], $registryDef->getMethodCalls());
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_website_search.placeholder.registry');

        $container->register('service.name.1')
            ->addTag('website_search.placeholder');
        $container->register('service.name.2')
            ->addTag('website_search.placeholder');
        $container->register('service.name.3')
            ->addTag('website_search.placeholder');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addPlaceholder', [new Reference('service.name.1')]],
                ['addPlaceholder', [new Reference('service.name.2')]],
                ['addPlaceholder', [new Reference('service.name.3')]]
            ],
            $registryDef->getMethodCalls()
        );
    }
}
