<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ComponentProcessorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ComponentProcessorPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ComponentProcessorPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ComponentProcessorPass();
    }

    public function testServiceNotExists()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testNoTaggedServices()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_product.component_processor.registry');

        $this->compiler->process($container);

        self::assertSame([], $registryDef->getMethodCalls());
    }

    public function testServiceExistsWithTaggedServices()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_product.component_processor.registry');

        $container->register('processor_1')
            ->addTag('oro_product.quick_add_processor');
        $container->register('processor_2')
            ->addTag('oro_product.quick_add_processor');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addProcessor', [new Reference('processor_1')]],
                ['addProcessor', [new Reference('processor_2')]]
            ],
            $registryDef->getMethodCalls()
        );
    }
}
