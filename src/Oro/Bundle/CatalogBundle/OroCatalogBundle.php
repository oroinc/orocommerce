<?php

namespace Oro\Bundle\CatalogBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\CatalogBundle\Entity\Category;

class OroCatalogBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container
            ->addCompilerPass(
                new DefaultFallbackExtensionPass(
                    [
                        Category::class => [
                            'title' => 'titles',
                            'shortDescription' => 'shortDescriptions',
                            'longDescription' => 'longDescriptions'
                        ]
                    ]
                )
            );
    }
}
