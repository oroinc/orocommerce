<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApplicationBundle\DependencyInjection\Compiler\RouterPrefixPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class RouterPrefixPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    /**
     * Environment setup
     */
    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')->getMock();
    }

    /**
     * Test process
     */
    public function testProcess()
    {
        $this->container->expects($this->exactly(3))
            ->method('getParameter')
            ->will($this->returnValueMap([
                ['kernel.name', 'app'],
                ['kernel.environment', 'dev'],
                ['kernel.application', 'admin']
            ]));

        $this->container->expects($this->once())
            ->method('setParameter')
            ->with(RouterPrefixPass::ROUTER_PREFIX, 'appDevAdmin');

        $compilerPass = new RouterPrefixPass();
        $compilerPass->process($this->container);
    }
}
