<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ApplicationBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;

class TwigSandboxConfigurationPassTest extends \PHPUnit_Framework_TestCase
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
        $securityPolicyDef = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $rendererDef = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $securityPolicyDef->expects($this->once())
            ->method('getArgument')
            ->will($this->returnValue([]));

        $rendererDef->expects($this->once())
            ->method('addMethodCall')
            ->with('addExtension', [new Reference(TwigSandboxConfigurationPass::APPLICATION_URL_EXTENSION)]);

        $this->container->expects($this->at(0))
            ->method('hasDefinition')
            ->with(TwigSandboxConfigurationPass::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY)
            ->will($this->returnValue(true));

        $this->container->expects($this->at(1))
            ->method('hasDefinition')
            ->with(TwigSandboxConfigurationPass::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY)
            ->will($this->returnValue(true));

        $this->container->expects($this->at(2))
            ->method('hasDefinition')
            ->with(TwigSandboxConfigurationPass::APPLICATION_URL_EXTENSION)
            ->will($this->returnValue(true));

        $this->container->expects($this->at(3))
            ->method('getDefinition')
            ->with(TwigSandboxConfigurationPass::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY)
            ->will($this->returnValue($securityPolicyDef));

        $this->container->expects($this->at(4))
            ->method('getDefinition')
            ->with(TwigSandboxConfigurationPass::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY)
            ->will($this->returnValue($rendererDef));

        $securityPolicyDef->expects($this->once())
            ->method('replaceArgument')
            ->with(4, ['application_url']);

        $compilerPass = new TwigSandboxConfigurationPass();
        $compilerPass->process($this->container);
    }

    /**
     * Test process without services
     */
    public function testProcessWithoutServices()
    {
        $this->container->expects($this->at(0))
            ->method('hasDefinition')
            ->with(TwigSandboxConfigurationPass::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY)
            ->will($this->returnValue(false));

        $this->container->expects($this->never())
            ->method('getDefinition');

        $compilerPass = new TwigSandboxConfigurationPass();
        $compilerPass->process($this->container);
    }
}
