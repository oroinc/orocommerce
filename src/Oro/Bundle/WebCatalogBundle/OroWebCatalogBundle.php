<?php

namespace Oro\Bundle\WebCatalogBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\WebCatalogDependenciesCompilerPass;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\OroWebCatalogExtension;
use Oro\Component\DependencyInjection\Compiler\InverseTaggedIteratorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The WebCatalogBundle bundle class.
 */
class OroWebCatalogBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroWebCatalogExtension();
        }

        return $this->extension;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new InverseTaggedIteratorCompilerPass(
            'oro_web_catalog.content_variant_type.registry',
            'oro_web_catalog.content_variant_type'
        ));
        $container->addCompilerPass(new InverseTaggedIteratorCompilerPass(
            'oro_web_catalog.content_variant_provider',
            'oro_web_catalog.content_variant_provider'
        ));
        $container->addCompilerPass(new WebCatalogDependenciesCompilerPass());

        $container->addCompilerPass(new DefaultFallbackExtensionPass([
            'Oro\Bundle\WebCatalogBundle\Entity\ContentNode' => [
                'title' => 'titles',
                'slugPrototype' => 'slugPrototypes'
            ]
        ]));
    }
}
