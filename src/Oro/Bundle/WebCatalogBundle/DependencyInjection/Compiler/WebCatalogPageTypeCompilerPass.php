<?php

namespace Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class WebCatalogPageTypeCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;
    
    const WEB_CATALOG_PAGE_TYPE_REGISTRY = 'oro_web_catalog.page_type.registry';
    const WEB_CATALOG_PAGE_TYPE_TAG = 'oro_web_catalog.page_type';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices(
            $container,
            self::WEB_CATALOG_PAGE_TYPE_REGISTRY,
            self::WEB_CATALOG_PAGE_TYPE_TAG,
            'addPageType'
        );
    }
}
