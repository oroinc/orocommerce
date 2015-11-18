<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ConditionPass;

class ConditionPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockBuilder */
    protected $definitionBuilder;

    /** @var ConditionPass */
    protected $compilerPass;

    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->definitionBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor();

        $this->compilerPass = new ConditionPass();
    }

    protected function tearDown()
    {
        unset($this->compilerPass, $this->definitionBuilder, $this->container);
    }

    public function testProcess()
    {
        $extensionDefinition = $this->definitionBuilder->getMock();
        $extensionDefinition->expects($this->once())
            ->method('replaceArgument')
            ->with(
                1,
                [
                    'condition_first' => 'condition.definition.first',
                    'condition_first_alias' => 'condition.definition.first',
                    'condition.definition.second' => 'condition.definition.second'
                ]
            );

        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with(ConditionPass::EXTENSION_SERVICE_ID)
            ->willReturn(true);
        $this->container->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValueMap([
                [ConditionPass::EXTENSION_SERVICE_ID, $extensionDefinition],
                ['condition.definition.first', $this->createServiceDefinition()],
                ['condition.definition.second', $this->createServiceDefinition()],
            ]));
        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(ConditionPass::EXPRESSION_TAG)
            ->willReturn(
                [
                    'condition.definition.first' => [['alias' => 'condition_first|condition_first_alias']],
                    'condition.definition.second' => [[]],
                ]
            );

        $compilerPass = new ConditionPass();
        $compilerPass->process($this->container);
    }

    public function testProcessWithoutConfigurationProvider()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with(ConditionPass::EXTENSION_SERVICE_ID)
            ->willReturn(false);
        $this->container->expects($this->never())
            ->method('getDefinition')
            ->with($this->anything());
        $this->container->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->compilerPass->process($this->container);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Definition
     */
    protected function createServiceDefinition()
    {
        $definition = $this->definitionBuilder->getMock();
        $definition->expects($this->once())
            ->method('setScope')
            ->with(ContainerInterface::SCOPE_PROTOTYPE)
            ->willReturn($definition);
        $definition->expects($this->once())
            ->method('setPublic')
            ->with(false)
            ->willReturn($definition);

        return $definition;
    }
}
