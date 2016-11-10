<?php

namespace Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContentVariantTypeCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;
    
    const REGISTRY_SERVICE = 'oro_web_catalog.content_variant_type.registry';
    const TAG = 'oro_web_catalog.content_variant_type';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices(
            $container,
            self::REGISTRY_SERVICE,
            self::TAG,
            'addContentVariantType'
        );
    }
}
