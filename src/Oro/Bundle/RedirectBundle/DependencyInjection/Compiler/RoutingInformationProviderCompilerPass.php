<?php

namespace Oro\Bundle\RedirectBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RoutingInformationProviderCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;
    
    const PROVIDER_REGISTRY = 'oro_redirect.provider.routing_information_provider';
    const TAG = 'oro_redirect.routing_information_provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices(
            $container,
            self::PROVIDER_REGISTRY,
            self::TAG,
            'registerProvider'
        );
    }
}
