<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use OroB2B\Bundle\FrontendBundle\DependencyInjection\CompilerPass\TestClientPass;

class TestClientPassTest extends \PHPUnit_Framework_TestCase
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
            ->with(TestClientPass::TEST_CLIENT_CLASS)
            ->willReturn($hasParameter);
        if ($hasParameter) {
            $container->expects($this->once())
                ->method('setParameter')
                ->with(TestClientPass::TEST_CLIENT_CLASS, TestClientPass::TEST_CLIENT_VALUE);
        } else {
            $container->expects($this->never())
                ->method('setParameter');
        }

        $compilerPass = new TestClientPass();
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
