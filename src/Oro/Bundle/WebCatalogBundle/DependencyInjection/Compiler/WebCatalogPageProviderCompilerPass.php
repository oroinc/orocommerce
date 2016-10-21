<?php

namespace Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class WebCatalogPageProviderCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;
    
    const WEB_CATALOG_PAGE_PROVIDER_REGISTRY = 'oro_web_catalog.page_provider.registry';
    const WEB_CATALOG_PAGE_PROVIDER_TAG = 'oro_web_catalog.page_provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices(
            $container,
            self::WEB_CATALOG_PAGE_PROVIDER_REGISTRY,
            self::WEB_CATALOG_PAGE_PROVIDER_TAG,
            'addPageProvider'
        );
    }
}
