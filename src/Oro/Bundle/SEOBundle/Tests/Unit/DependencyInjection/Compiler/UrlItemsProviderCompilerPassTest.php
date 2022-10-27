<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SEOBundle\DependencyInjection\Compiler\UrlItemsProviderCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

class UrlItemsProviderCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    private const REGISTRY_SERVICE_ID = 'test_service_registry';
    private const TAG_NAME            = 'test_tag';

    /** @var UrlItemsProviderCompilerPass */
    private $compiler;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->compiler = new UrlItemsProviderCompilerPass(self::REGISTRY_SERVICE_ID, self::TAG_NAME);
    }

    public function testProcessCompositeDoesNotExist()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcessNoTaggedServicesFound()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register(self::REGISTRY_SERVICE_ID)
            ->addArgument([]);

        $this->compiler->process($container);

        self::assertSame([], $registryDef->getArgument(0));
    }

    public function testProcessWithTaggedServices()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register(self::REGISTRY_SERVICE_ID)
            ->addArgument([]);

        $container->register('service.name.1')
            ->addTag(self::TAG_NAME, ['alias' => 'taggedService1Alias']);
        $container->register('service.name.2')
            ->addTag(self::TAG_NAME, ['alias' => 'taggedService2Alias']);

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
        $container->register(self::REGISTRY_SERVICE_ID)
            ->addArgument([]);

        $container->register('service.name.1')
            ->addTag(self::TAG_NAME);

        $this->compiler->process($container);
    }
}
