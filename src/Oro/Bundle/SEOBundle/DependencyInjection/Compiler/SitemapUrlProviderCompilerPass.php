<?php

namespace Oro\Bundle\SEOBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SitemapUrlProviderCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;
    
    const PROVIDER_REGISTRY = 'oro_seo.provider.sitemap_url_provider_registry';
    const TAG = 'oro_seo.sitemap_url_provider';

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
