<?php

namespace Oro\Bundle\PaymentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass allows to use payment notify route for guest user.
 */
class PaymentGuestAccessUrlPass implements CompilerPassInterface
{
    public const URL_PROVIDER = 'oro_frontend.guest_access.provider.guest_access_urls_provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $urlProviderDefinition = $container->getDefinition(self::URL_PROVIDER);
        // Allow payment notify callback transaction.
        $urlProviderDefinition->addMethodCall('addAllowedUrlPattern', ['^/payment/callback/notify/']);
    }
}
