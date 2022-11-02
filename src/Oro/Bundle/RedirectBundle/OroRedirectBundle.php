<?php

namespace Oro\Bundle\RedirectBundle;

use Oro\Bundle\RedirectBundle\DependencyInjection\Compiler\SecurityFirewallCompilerPass;
use Oro\Component\DependencyInjection\Compiler\PriorityNamedTaggedServiceCompilerPass;
use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroRedirectBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new PriorityNamedTaggedServiceCompilerPass(
            'oro_redirect.provider.routing_information_provider',
            'oro_redirect.routing_information_provider',
            'alias'
        ));
        $container->addCompilerPass(new PriorityTaggedLocatorCompilerPass(
            'oro_redirect.provider.context_url_provider_registry',
            'oro_redirect.context_url_provider',
            'alias'
        ));
        $container->addCompilerPass(new SecurityFirewallCompilerPass());
    }
}
