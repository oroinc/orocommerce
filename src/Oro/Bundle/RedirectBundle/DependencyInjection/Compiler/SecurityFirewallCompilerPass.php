<?php

namespace Oro\Bundle\RedirectBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Sets "oro_redirect.security.firewall" service as the main security firewall listener.
 */
class SecurityFirewallCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasParameter('kernel.debug') && $container->getParameter('kernel.debug')) {
            // in dev mode, "debug.security.firewall" service overrides the security.firewall service by alias.
            // remove tags from "debug.security.firewall" service and set it's reference as firewall to
            // "oro_redirect.security.firewall" service that will be the main firewall listener.
            $container->getDefinition('debug.security.firewall')->clearTags();
            $container->getDefinition('oro_redirect.security.firewall')
                ->addMethodCall('setFirewall', [new Reference('debug.security.firewall')]);
        } else {
            // remove tags from "security.firewall" service and set it's definition as firewall to
            // "oro_redirect.security.firewall" service that will be the main firewall listener.
            $container->getDefinition('security.firewall')->clearTags();
            $container->getDefinition('oro_redirect.security.firewall')
                ->addMethodCall('setFirewall', [$container->getDefinition('security.firewall')]);
        }

        // set "oro_redirect.security.firewall" service as the main firewall listener
        $container->setAlias('security.firewall', 'oro_redirect.security.firewall');
    }
}
