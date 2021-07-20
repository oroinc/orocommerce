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
    /**
     * @var ResolverEventConnectorPass
     */
    protected $compilerPass;

    /**
     * @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $containerBuilder;

    protected function setUp(): void
    {
        $this->containerBuilder = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $this->compilerPass = new ResolverEventConnectorPass();
    }

    public function testNoTaggedServices()
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn([]);

        $this->containerBuilder
            ->expects($this->never())
            ->method('setDefinition');

        $this->compilerPass->process($this->containerBuilder);
    }

    /**
     * @dataProvider emptyTagsDataProvider
     */
    public function testEmptyTags(array $tags, array $exception = [])
    {
        if ($exception) {
            list($exception, $message) = $exception;
            $this->expectException($exception);
            $this->expectExceptionMessage($message);
        }

        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn(['oro_tax.resolver.total' => $tags]);

        $this->containerBuilder
            ->expects($this->never())
            ->method('setDefinition');

        $this->compilerPass->process($this->containerBuilder);
    }

    /**
     * @return array
     */
    public function emptyTagsDataProvider()
    {
        return [
            'empty tag' => [[]],
            'empty event' => [
                [['priority' => -255]],
                ['\InvalidArgumentException', 'Wrong tags configuration "[{"priority":-255}]"'],
            ],
        ];
    }

    public function testTag()
    {
        $id = 'oro_tax.resolver.total';
        $class = CustomResolverEventConnector::class;

        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn(
                [
                    $id => [
                        ['event' => ResolveTaxEvent::RESOLVE, 'priority' => -255],
                        ['event' => ResolveTaxEvent::RESOLVE, 'priority' => 255],
                    ],
                ]
            );

        $this->containerBuilder
            ->expects($this->once())
            ->method('hasParameter')
            ->with(ResolverEventConnectorPass::CONNECTOR_CLASS)
            ->willReturn(true);
        $this->containerBuilder
            ->expects($this->once())
            ->method('getParameter')
            ->with(ResolverEventConnectorPass::CONNECTOR_CLASS)
            ->willReturn($class);

        $this->containerBuilder
            ->expects($this->once())
            ->method('setDefinition')
            ->with(
                sprintf('%s.%s', $id, ResolverEventConnectorPass::CONNECTOR_SERVICE_NAME_SUFFIX),
                $this->callback(
                    function (Definition $definition) use ($class, $id) {
                        $this->assertEquals($class, $definition->getClass());
                        $this->assertEquals([new Reference($id)], $definition->getArguments());
                        $this->assertTrue($definition->hasTag('kernel.event_listener'));
                        $this->assertEquals(
                            [
                                ['event' => ResolveTaxEvent::RESOLVE, 'method' => 'onResolve', 'priority' => -255],
                                ['event' => ResolveTaxEvent::RESOLVE, 'method' => 'onResolve', 'priority' => 255],
                            ],
                            $definition->getTag('kernel.event_listener')
                        );

                        $this->assertTrue(method_exists($class, 'onResolve'));

                        return true;
                    }
                )
            );

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testDefaultResolverEventConnector()
    {
        $id = 'oro_tax.resolver.total';
        $class = ResolverEventConnector::class;

        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn(
                [
                    $id => [
                        ['event' => ResolveTaxEvent::RESOLVE, 'priority' => -255],
                        ['event' => ResolveTaxEvent::RESOLVE, 'priority' => 255],
                    ],
                ]
            );

        $this->containerBuilder
            ->expects($this->once())
            ->method('hasParameter')
            ->with(ResolverEventConnectorPass::CONNECTOR_CLASS)
            ->willReturn(false);
        $this->containerBuilder
            ->expects($this->never())
            ->method('getParameter')
            ->with(ResolverEventConnectorPass::CONNECTOR_CLASS);

        $this->containerBuilder
            ->expects($this->once())
            ->method('setDefinition')
            ->with(
                sprintf('%s.%s', $id, ResolverEventConnectorPass::CONNECTOR_SERVICE_NAME_SUFFIX),
                $this->callback(
                    function (Definition $definition) use ($class, $id) {
                        $this->assertEquals($class, $definition->getClass());
                        $this->assertEquals([new Reference($id)], $definition->getArguments());
                        $this->assertTrue($definition->hasTag('kernel.event_listener'));
                        $this->assertEquals(
                            [
                                ['event' => ResolveTaxEvent::RESOLVE, 'method' => 'onResolve', 'priority' => -255],
                                ['event' => ResolveTaxEvent::RESOLVE, 'method' => 'onResolve', 'priority' => 255],
                            ],
                            $definition->getTag('kernel.event_listener')
                        );

                        $this->assertTrue(method_exists($class, 'onResolve'));

                        return true;
                    }
                )
            );

        $this->compilerPass->process($this->containerBuilder);
    }
}
