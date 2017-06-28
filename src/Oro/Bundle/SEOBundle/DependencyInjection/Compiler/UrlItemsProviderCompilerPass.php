<?php

namespace Oro\Bundle\SEOBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class UrlItemsProviderCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;
    
    const PROVIDER_REGISTRY = 'oro_seo.sitemap.provider.url_items_provider_registry';
    const TAG = 'oro_seo.sitemap.url_items_provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices(
            $container,
            self::PROVIDER_REGISTRY,
            self::TAG,
            'addProvider'
        );
    }
}
