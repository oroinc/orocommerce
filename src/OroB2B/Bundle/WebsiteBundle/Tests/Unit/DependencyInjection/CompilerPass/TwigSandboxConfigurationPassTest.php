<?php

namespace OroB2B\Bundle\WebsiteBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class TwigSandboxConfigurationPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessSkip()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(['has', 'getDefinition'])
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('has')
            ->with(TwigSandboxConfigurationPass::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY)
            ->willReturn(false);

        $container->expects($this->never())
            ->method('getDefinition');

        $compilerPass = new TwigSandboxConfigurationPass();
        $compilerPass->process($container);
    }
    
    public function testProcess()
    {
        /** @var Definition|\PHPUnit_Framework_MockObject_MockObject $securityPolicyDefinition */
        $securityPolicyDefinition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $securityPolicyDefinition->expects($this->once())
            ->method('getArgument')
            ->with(4)
            ->willReturn([
                'some_existing_function'
            ]);

        $securityPolicyDefinition->expects($this->once())
            ->method('replaceArgument')
            ->with(4, [
                'some_existing_function',
                'website_path',
                'website_secure_path'
            ]);

        /** @var Definition|\PHPUnit_Framework_MockObject_MockObject $emailRendererDefinition */
        $emailRendererDefinition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $emailRendererDefinition->expects($this->once())
            ->method('addMethodCall')
            ->with('addExtension', [new Reference(TwigSandboxConfigurationPass::WEBSITE_PATH_EXTENSION_SERVICE_KEY)]);

        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->any())
            ->method('has')
            ->willReturnMap([
                [TwigSandboxConfigurationPass::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY, true],
                [TwigSandboxConfigurationPass::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY, true]
            ]);

        $container->expects($this->any())
            ->method('getDefinition')
            ->willReturnMap([
                [
                    TwigSandboxConfigurationPass::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY,
                    $securityPolicyDefinition
                ],
                [
                    TwigSandboxConfigurationPass::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY,
                    $emailRendererDefinition
                ]
            ]);

        $compilerPass = new TwigSandboxConfigurationPass();
        $compilerPass->process($container);
    }
}
