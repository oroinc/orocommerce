<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler\AddCheckoutToSignInExcludedRoutesPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddCheckoutToSignInExcludedRoutesPassTest extends TestCase
{
    private AddCheckoutToSignInExcludedRoutesPass $compiler;

    #[\Override]
    protected function setUp(): void
    {
        $this->compiler = new AddCheckoutToSignInExcludedRoutesPass();
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $providerDef = $container->register('oro_customer.provider.sign_in.target_path');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addExcludedRoute', ['oro_checkout_frontend_checkout']],
            ],
            $providerDef->getMethodCalls()
        );
    }
}
