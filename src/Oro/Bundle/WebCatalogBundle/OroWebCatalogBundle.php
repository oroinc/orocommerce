<?php

namespace Oro\Bundle\WebCatalogBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\WebCatalogPageProviderCompilerPass;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\WebCatalogPageTypeCompilerPass;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\OroWebCatalogExtension;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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
        $container->addCompilerPass(new WebCatalogPageTypeCompilerPass());
        $container->addCompilerPass(new WebCatalogPageProviderCompilerPass());

        $container
            ->addCompilerPass(
                new DefaultFallbackExtensionPass(
                    [
                        ContentNode::class => [
                            'title' => 'titles',
                            'slug' => 'slugs'
                        ]
                    ]
                )
            );
    }
}
