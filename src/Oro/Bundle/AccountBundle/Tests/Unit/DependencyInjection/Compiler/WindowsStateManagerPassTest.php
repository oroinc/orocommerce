<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\AccountBundle\DependencyInjection\Compiler\WindowsStateManagerPass;

class WindowsStateManagerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessWithoutDefinition()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $containerBuilder->expects($this->once())->method('hasDefinition')->willReturn(false);
        $containerBuilder->expects($this->never())->method('getDefinition');

        $compilerPass = new WindowsStateManagerPass();
        $compilerPass->process($containerBuilder);
    }

    public function testProcess()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $containerBuilder->expects($this->once())->method('hasDefinition')->willReturn(true);

        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with(
                $this->isType('string'),
                $this->callback(
                    function (array $arguments) {
                        $this->assertArrayHasKey(0, $arguments);
                        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $arguments[0]);

                        return true;
                    }
                )
            );

        $containerBuilder->expects($this->once())->method('getDefinition')->willReturn($definition);

        $compilerPass = new WindowsStateManagerPass();
        $compilerPass->process($containerBuilder);
    }
}
