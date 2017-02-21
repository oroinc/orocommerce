<?php

namespace Oro\Bundle\RedirectBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContextUrlProviderCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;
    
    const PROVIDER_REGISTRY = 'oro_redirect.provider.context_url_provider_registry';
    const TAG = 'oro_redirect.context_url_provider';

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
