<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\TaxBundle\DependencyInjection\CompilerPass\ResolverEventConnectorPass;
use Oro\Bundle\TaxBundle\Event\ResolverEventConnector;
use Oro\Bundle\TaxBundle\Event\ResolveTaxEvent;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ResolverEventConnectorPassTest extends \PHPUnit\Framework\TestCase
{
    /**@var ResolverEventConnectorPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ResolverEventConnectorPass();
    }

    public function testNoTaggedServices()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testEmptyEvent()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Wrong tags configuration "[{"priority":-255}]"');

        $container = new ContainerBuilder();

        $container->register('resolver_1')
            ->addTag('oro_tax.resolver', ['priority' => -255]);

        $this->compiler->process($container);
    }

    public function testDefaultResolverEventConnector()
    {
        $container = new ContainerBuilder();

        $container->register('resolver_1')
            ->addTag('oro_tax.resolver', ['event' => ResolveTaxEvent::RESOLVE])
            ->addTag('oro_tax.resolver', ['event' => ResolveTaxEvent::RESOLVE, 'priority' => 255]);

        $this->compiler->process($container);

        $expectedEventConnectorForResolver1Def = new Definition(
            ResolverEventConnector::class,
            [new Reference('resolver_1')]
        );
        $expectedEventConnectorForResolver1Def->addTag(
            'kernel.event_listener',
            ['event' => ResolveTaxEvent::RESOLVE, 'method' => 'onResolve']
        );
        $expectedEventConnectorForResolver1Def->addTag(
            'kernel.event_listener',
            ['event' => ResolveTaxEvent::RESOLVE, 'method' => 'onResolve', 'priority' => 255]
        );

        self::assertEquals(
            $expectedEventConnectorForResolver1Def,
            $container->getDefinition('resolver_1.event.resolver_event_connector')
        );
    }

    public function testCustomResolverEventConnector()
    {
        $container = new ContainerBuilder();
        $container->setParameter(
            'oro_tax.event.resolver_event_connector.common_class',
            CustomResolverEventConnector::class
        );

        $container->register('resolver_1')
            ->addTag('oro_tax.resolver', ['event' => ResolveTaxEvent::RESOLVE])
            ->addTag('oro_tax.resolver', ['event' => ResolveTaxEvent::RESOLVE, 'priority' => 255]);

        $this->compiler->process($container);

        $expectedEventConnectorForResolver1Def = new Definition(
            CustomResolverEventConnector::class,
            [new Reference('resolver_1')]
        );
        $expectedEventConnectorForResolver1Def->addTag(
            'kernel.event_listener',
            ['event' => ResolveTaxEvent::RESOLVE, 'method' => 'onResolve']
        );
        $expectedEventConnectorForResolver1Def->addTag(
            'kernel.event_listener',
            ['event' => ResolveTaxEvent::RESOLVE, 'method' => 'onResolve', 'priority' => 255]
        );

        self::assertEquals(
            $expectedEventConnectorForResolver1Def,
            $container->getDefinition('resolver_1.event.resolver_event_connector')
        );
    }
}
