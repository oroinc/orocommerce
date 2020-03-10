<?php

namespace Oro\Bundle\CatalogBundle;

use Oro\Bundle\CatalogBundle\DependencyInjection\CompilerPass\AttributeBlockTypeMapperPass;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The CatalogBundle bundle class.
 */
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
                        'Oro\Bundle\CatalogBundle\Entity\Category' => [
                            'title' => 'titles',
                            'shortDescription' => 'shortDescriptions',
                            'longDescription' => 'longDescriptions',
                            'slugPrototype' => 'slugPrototypes'
                        ]
                    ]
                )
            )
            ->addCompilerPass(new AttributeBlockTypeMapperPass());
    }
}
