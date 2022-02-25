<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\SubtotalProviderPass;
use Oro\Bundle\PricingBundle\SubtotalProcessor\SubtotalProviderRegistry;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class SubtotalProviderPassTest extends \PHPUnit\Framework\TestCase
{
    private ContainerBuilder $container;

    private SubtotalProviderPass $compiler;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new SubtotalProviderPass();
    }

    public function testProcessWhenNoTaggedServices(): void
    {
        $registry = $this->container->register(
            'oro_pricing.subtotal_processor.subtotal_provider_registry',
            SubtotalProviderRegistry::class
        );

        $this->compiler->process($this->container);

        self::assertEquals([], $registry->getArgument('$providerNames'));

        $serviceLocatorReference = $registry->getArgument('$providerContainer');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals([], $serviceLocatorDef->getArgument(0));
    }

    public function testProcessWithTaggedServices(): void
    {
        $registry = $this->container->register(
            'oro_pricing.subtotal_processor.subtotal_provider_registry',
            SubtotalProviderRegistry::class
        );

        $this->container->setDefinition('service_name_1', new Definition())
            ->addTag('oro_pricing.subtotal_provider', ['alias' => 'provider1', 'priority' => 1]);
        $this->container->setDefinition('service_name_2', new Definition())
            ->addTag('oro_pricing.subtotal_provider', ['alias' => 'provider2']);
        $this->container->setDefinition('service_name_3', new Definition())
            ->addTag('oro_pricing.subtotal_provider', ['alias' => 'provider3', 'priority' => -255]);
        $this->container->setDefinition('service_name_4', new Definition())
            ->addTag('oro_pricing.subtotal_provider', ['alias' => 'provider4', 'priority' => 255]);

        $this->compiler->process($this->container);

        self::assertEquals(
            ['provider3', 'provider2', 'provider1', 'provider4'],
            $registry->getArgument('$providerNames')
        );

        $serviceLocatorReference = $registry->getArgument('$providerContainer');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'provider1' => new ServiceClosureArgument(new Reference('service_name_1')),
                'provider2' => new ServiceClosureArgument(new Reference('service_name_2')),
                'provider3' => new ServiceClosureArgument(new Reference('service_name_3')),
                'provider4' => new ServiceClosureArgument(new Reference('service_name_4'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
