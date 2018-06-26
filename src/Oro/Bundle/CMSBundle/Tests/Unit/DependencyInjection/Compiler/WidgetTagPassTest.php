<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\WidgetTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class WidgetTagPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var WidgetTagPass */
    private $pass;

    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    protected function setUp()
    {
        $this->pass = new WidgetTagPass();
        $this->container = $this->createMock(ContainerBuilder::class);
    }

    public function testProcess()
    {
        $registryDefinition = $this->getDefinitionMock();
        $firstWidgetDefinition = $this->getDefinitionMock();
        $firstWidgetDefinition->expects($this->once())
            ->method('setPublic')
            ->with(false);
        $secondWidgetDefinition = $this->getDefinitionMock();
        $secondWidgetDefinition->expects($this->once())
            ->method('setPublic')
            ->with(false);
        $this->container->expects($this->exactly(3))
            ->method('getDefinition')
            ->withConsecutive(
                ['oro_cms.widget_registry'],
                ['service.first_widget_alias.provider'],
                ['service.second_widget_alias.provider']
            )
            ->willReturnOnConsecutiveCalls(
                $registryDefinition,
                $firstWidgetDefinition,
                $secondWidgetDefinition
            );

        $widgets = [
            'first_widget_alias' => $firstWidgetDefinition,
            'second_widget_alias' => $secondWidgetDefinition,
        ];

        $providers = [
            'service.first_widget_alias.provider' => [
                ['alias' => 'first_widget_alias'],
            ],
            'service.second_widget_alias.provider' => [
                ['alias' => 'second_widget_alias'],
            ],
        ];
        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn($providers);

        $registryDefinition->expects($this->once())
            ->method('replaceArgument')
            ->with(1, $widgets);

        $this->pass->process($this->container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\LogicException
     * @expectedExceptionMessage Widget alias isn't defined for "service.first_widget_alias.provider".
     */
    public function testProcessWidgetWithoutAlias()
    {
        $registryDefinition = $this->getDefinitionMock();
        $firstWidgetDefinition = $this->getDefinitionMock();
        $firstWidgetDefinition->expects($this->once())
            ->method('setPublic')
            ->with(false);
        $secondWidgetDefinition = $this->getDefinitionMock();
        $this->container->expects($this->exactly(2))
            ->method('getDefinition')
            ->withConsecutive(
                ['oro_cms.widget_registry'],
                ['service.first_widget_alias.provider']
            )
            ->willReturnOnConsecutiveCalls(
                $registryDefinition,
                $firstWidgetDefinition,
                $secondWidgetDefinition
            );

        $providers = [
            'service.first_widget_alias.provider' => [
                []
            ],
            'service.second_widget_alias.provider' => [
                ['alias' => 'second_widget_alias'],
            ],
        ];
        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn($providers);

        $this->pass->process($this->container);
    }

    public function testProcessWithoutRegistry()
    {
        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with('oro_cms.widget_registry')
            ->willReturn(null);

        $this->container->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->pass->process($this->container);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Definition
     */
    private function getDefinitionMock()
    {
        return $this->createMock(Definition::class);
    }
}
