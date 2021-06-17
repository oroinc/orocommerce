<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SaleBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TwigSandboxConfigurationPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $securityPolicyDef = $container->register('oro_email.twig.email_security_policy')
            ->setArguments([[], [], [], [], ['some_existing_function']]);
        $rendererDef = $container->register('oro_email.email_renderer');

        $compiler = new TwigSandboxConfigurationPass();
        $compiler->process($container);

        self::assertEquals(
            [
                [],
                [],
                [],
                [],
                ['some_existing_function', 'quote_guest_access_link']
            ],
            $securityPolicyDef->getArguments()
        );
        self::assertEquals(
            [
                ['addExtension', [new Reference('oro_sale.twig.quote_guest_access')]]
            ],
            $rendererDef->getMethodCalls()
        );
    }
}
