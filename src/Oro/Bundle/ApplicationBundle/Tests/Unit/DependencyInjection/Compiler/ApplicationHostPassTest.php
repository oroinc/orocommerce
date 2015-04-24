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

    /**
     * Test process
     */
    public function testProcess()
    {
        $this->container->expects($this->exactly(3))
            ->method('getParameter')
            ->will(
                $this->returnCallback(function($arg) {
                    $map = [
                        'kernel.name'        => 'app',
                        'kernel.environment' => 'dev',
                        'kernel.application' => 'admin'
                    ];

                    return $map[$arg];
                })
            );

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

        $this->container->expects($this->exactly(2))
            ->method('setParameter')
            ->will(
                $this->returnCallback(function($arg) use ($expectedParameters) {
                    $map = [
                        ApplicationHostPass::PARAMETER_NAME => $expectedParameters,
                        'router.cache_class_prefix'         => 'appDevAdmin'
                    ];

                    return $map[$arg];
                })
            );

        $compilerPass = new ApplicationHostPass();
        $compilerPass->process($this->container);
    }
}
