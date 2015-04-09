<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

use Oro\Bundle\ApplicationBundle\DependencyInjection\Compiler\ApplicationHostPass;

class ApplicationHostPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    /**
     * Environment setup
     */
    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')->getMock();
    }

    public function testProcess()
    {
        $allParameters = [
            'application_host.admin'    => 'http://localhost/admin.php',
            'application_host.frontend' => 'http://localhost/',
            'application_host.install'  => 'http://localhost/install.php',
            'application_host.tracking' => 'http://localhost/tracking.php',
            'some_parameter'            => 'some_value',
            'another_parameter'         => 'another_value',
        ];

        $expectedParameters = [
            'admin'    => $allParameters['application_host.admin'],
            'frontend' => $allParameters['application_host.frontend'],
            'install'  => $allParameters['application_host.install'],
            'tracking' => $allParameters['application_host.tracking'],
        ];

        $this->container->expects($this->once())
            ->method('getParameterBag')
            ->willReturn(new ParameterBag($allParameters));

        $this->container->expects($this->once())
            ->method('setParameter')
            ->with(ApplicationHostPass::PARAMETER_NAME, $expectedParameters);

        $compilerPass = new ApplicationHostPass();
        $compilerPass->process($this->container);
    }
}
