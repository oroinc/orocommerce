<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApplicationBundle\DependencyInjection\Compiler\ApplicationHostPass;

class ApplicationHostPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\DependencyInjection\ContainerBuilder
     */
    private $container;

    /**
     * Environment setup
     */
    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')->getMock();
    }

    public function testProcess()
    {
        $this->container->expects($this->exactly(4))
            ->method('hasParameter')
            ->will(
                $this->returnValueMap(
                    [
                        ['application_host.admin', true],
                        ['application_host.frontend', true],
                        ['application_host.install', true],
                        ['application_host.tracking', true],
                    ]
                )
            );
        $this->container->expects($this->exactly(4))
            ->method('getParameter')
            ->will(
                $this->returnValueMap(
                    [
                        ['application_host.admin', 'http://localhost/admin.php'],
                        ['application_host.frontend', 'http://localhost/frontend.php'],
                        ['application_host.install', 'http://localhost/install.php'],
                        ['application_host.tracking', 'http://localhost/tracking.php'],
                    ]
                )
            );
        $this->container->expects($this->once())
            ->method('setParameter')
            ->with($this->equalTo(ApplicationHostPass::PARAMETER_NAME));

        $compilerPass = new ApplicationHostPass();
        $compilerPass->process($this->container);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Parameter `application_host.admin` must be defined.
     */
    public function testProcessNotRegisteredHostParameter()
    {
        $this->container->expects($this->once())
            ->method('hasParameter')
            ->with($this->equalTo('application_host.admin'))
            ->will($this->returnValue(false));

        $compilerPass = new ApplicationHostPass();
        $compilerPass->process($this->container);
    }
}
