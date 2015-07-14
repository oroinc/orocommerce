<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use OroB2B\Bundle\CustomerBundle\DependencyInjection\Compiler\DataAuditEntityMappingPass;

class DataAuditEntityMappingPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $definition->expects($this->exactly(2))
            ->method('addMethodCall')
            ->withConsecutive(
                ['addAuditEntryClass', $this->isType('array')],
                ['addAuditEntryFieldClass', $this->isType('array')]
            );

        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(DataAuditEntityMappingPass::MAPPER_SERVICE)
            ->willReturn($definition);

        $containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(DataAuditEntityMappingPass::MAPPER_SERVICE)
            ->willReturn($definition);

        $containerBuilder->expects($this->exactly(3))
            ->method('getParameter')
            ->with($this->isType('string'));

        $compilerPass = new DataAuditEntityMappingPass();
        $compilerPass->process($containerBuilder);
    }

    public function testProcessWithoutDefinition()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $containerBuilder->expects($this->never())->method('getDefinition');
        $containerBuilder->expects($this->never())->method('getParameter');

        $containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(DataAuditEntityMappingPass::MAPPER_SERVICE)
            ->willReturn(false);

        $compilerPass = new DataAuditEntityMappingPass();
        $compilerPass->process($containerBuilder);
    }
}
