<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\RFPBundle\DependencyInjection\CompilerPass\TwigSandboxConfigurationPass;

class TwigSandboxConfigurationPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var CompilerPassInterface */
    private $compilerPass;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder */
    private $containerBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->containerBuilder = $this->createMock(ContainerBuilder::class);
        $this->compilerPass = new TwigSandboxConfigurationPass();
    }

    public function testProcessWithoutEmailSecurityPoliceService()
    {
        $this->containerBuilder
            ->expects($this->at(0))
            ->method('hasDefinition')
            ->with('oro_email.twig.email_security_policy')
            ->willReturn(false);

        $this->containerBuilder
            ->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessWithoutEmailRendererService()
    {
        $this->containerBuilder
            ->expects($this->at(0))
            ->method('hasDefinition')
            ->with('oro_email.twig.email_security_policy')
            ->willReturn(true);

        $this->containerBuilder
            ->expects($this->at(1))
            ->method('hasDefinition')
            ->with('oro_email.email_renderer')
            ->willReturn(false);

        $this->containerBuilder
            ->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcess()
    {
        $this->containerBuilder
            ->expects($this->at(0))
            ->method('hasDefinition')
            ->with('oro_email.twig.email_security_policy')
            ->willReturn(true);

        $this->containerBuilder
            ->expects($this->at(1))
            ->method('hasDefinition')
            ->with('oro_email.email_renderer')
            ->willReturn(true);

        $securityPolicyDef = $this->createMock(Definition::class);
        $securityPolicyDef->expects($this->once())
            ->method('replaceArgument');

        $this->containerBuilder
            ->expects($this->at(2))
            ->method('getDefinition')
            ->with('oro_email.twig.email_security_policy')
            ->willReturn($securityPolicyDef);

        $rendererDef = $this->createMock(Definition::class);
        $rendererDef->expects($this->once())
            ->method('addMethodCall')
            ->with('addExtension', [new Reference('oro_rfp.twig.request_products')]);

        $this->containerBuilder
            ->expects($this->at(3))
            ->method('getDefinition')
            ->with('oro_email.email_renderer')
            ->willReturn($rendererDef);

        $this->compilerPass->process($this->containerBuilder);
    }
}
