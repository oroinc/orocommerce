<?php

namespace Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Excludes the frontend checkout route from the sign-in target path.
 */
class AddCheckoutToSignInExcludedRoutesPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('oro_customer.provider.sign_in.target_path')
            ->addMethodCall('addExcludedRoute', ['oro_checkout_frontend_checkout']);
    }
}
