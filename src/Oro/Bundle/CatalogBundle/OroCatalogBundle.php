<?php

namespace Oro\Bundle\CatalogBundle;

use Oro\Bundle\CatalogBundle\DependencyInjection\CompilerPass\AttributeBlockTypeMapperPass;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\EntityFallbackFieldsStoragePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroCatalogBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new EntityFallbackFieldsStoragePass([
            'Oro\Bundle\CatalogBundle\Entity\Category' => [
                'title' => 'titles',
                'shortDescription' => 'shortDescriptions',
                'longDescription' => 'longDescriptions',
                'slugPrototype' => 'slugPrototypes'
            ]
        ]));
        $container->addCompilerPass(new AttributeBlockTypeMapperPass());
    }
}
