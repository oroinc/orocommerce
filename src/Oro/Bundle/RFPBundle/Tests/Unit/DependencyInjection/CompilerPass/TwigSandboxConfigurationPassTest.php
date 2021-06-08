<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\RFPBundle\DependencyInjection\CompilerPass\TwigSandboxConfigurationPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TwigSandboxConfigurationPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var CompilerPassInterface */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new TwigSandboxConfigurationPass();
    }

    public function testProcessWithoutEmailSecurityPoliceService()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcessWithoutEmailRendererService()
    {
        $container = new ContainerBuilder();
        $container->register('oro_email.twig.email_security_policy');

        $this->compiler->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $securityPolicyDef = $container->register('oro_email.twig.email_security_policy')
            ->setArguments([[], [], [], [], ['func1']]);
        $rendererDef = $container->register('oro_email.email_renderer');

        $this->compiler->process($container);

        self::assertEquals(
            ['func1', 'rfp_products'],
            $securityPolicyDef->getArgument(4)
        );
        self::assertEquals(
            [
                ['addExtension', [new Reference('oro_rfp.twig.request_products')]]
            ],
            $rendererDef->getMethodCalls()
        );
    }
}
