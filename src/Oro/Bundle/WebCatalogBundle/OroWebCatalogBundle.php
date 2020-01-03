<?php

namespace Oro\Bundle\WebCatalogBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\ContentVariantProviderCompilerPass;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\ContentVariantTypeCompilerPass;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\WebCatalogDependenciesCompilerPass;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\OroWebCatalogExtension;
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
        $container->addCompilerPass(new ContentVariantTypeCompilerPass());
        $container->addCompilerPass(new ContentVariantProviderCompilerPass());
        $container->addCompilerPass(new WebCatalogDependenciesCompilerPass());

        $container->addCompilerPass(new DefaultFallbackExtensionPass([
            'Oro\Bundle\WebCatalogBundle\Entity\ContentNode' => [
                'title' => 'titles',
                'slugPrototype' => 'slugPrototypes'
            ]
        ]));
    }
}
