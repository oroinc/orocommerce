<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\FrontendBundle\DependencyInjection\CompilerPass\ExceptionControllerCompilerPass;

class ExceptionControllerCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param bool $hasParameter
     * @dataProvider processDataProvider
     */
    public function testProcess($hasParameter)
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->once())
            ->method('hasParameter')
            ->with(ExceptionControllerCompilerPass::CONTROLLER_PARAMETER)
            ->willReturn($hasParameter);
        if ($hasParameter) {
            $container->expects($this->once())
                ->method('setParameter')
                ->with(
                    ExceptionControllerCompilerPass::CONTROLLER_PARAMETER,
                    ExceptionControllerCompilerPass::CONTROLLER_VALUE
                );
        } else {
            $container->expects($this->never())
                ->method('setParameter');
        }

        $compilerPass = new ExceptionControllerCompilerPass();
        $compilerPass->process($container);
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        return [
            'parameter defined' => [true],
            'parameter undefined' => [false],
        ];
    }
}
