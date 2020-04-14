<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SaleBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class TwigSandboxConfigurationPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $containerBuilder;

    /** @var TwigSandboxConfigurationPass */
    private $compilerPass;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->containerBuilder = $this->createMock(ContainerBuilder::class);

        $this->compilerPass = new TwigSandboxConfigurationPass();
    }

    public function testProcess(): void
    {
        $securityPolicyDef = $this->createMock(Definition::class);
        $securityPolicyDef->expects($this->once())
            ->method('getArgument')
            ->with(4)
            ->willReturn(['test']);
        $securityPolicyDef->expects($this->once())
            ->method('replaceArgument')
            ->with(4, ['test', 'quote_guest_access_link']);

        $rendererDef = $this->createMock(Definition::class);
        $rendererDef->expects($this->once())
            ->method('addMethodCall')
            ->with('addExtension', [new Reference('oro_sale.twig.quote_guest_access')]);

        $this->containerBuilder
            ->expects($this->exactly(2))
            ->method('getDefinition')
            ->willReturnMap(
                [
                    [
                        TwigSandboxConfigurationPass::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY,
                        $securityPolicyDef
                    ],
                    [
                        TwigSandboxConfigurationPass::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY,
                        $rendererDef
                    ]
                ]
            );

        $this->compilerPass->process($this->containerBuilder);
    }
}
