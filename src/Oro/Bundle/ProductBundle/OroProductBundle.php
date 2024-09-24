<?php

namespace Oro\Bundle\ProductBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\EntityFallbackFieldsStoragePass;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\AttributeBlockTypeMapperPass;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ProductCollectionCompilerPass;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\TwigSandboxConfigurationPass;
use Oro\Component\DependencyInjection\Compiler\PriorityNamedTaggedServiceCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroProductBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new PriorityNamedTaggedServiceCompilerPass(
            'oro_product.component_processor.registry',
            'oro_product.quick_add_processor',
            'processor_name'
        ));
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        $container->addCompilerPass(new EntityFallbackFieldsStoragePass([
            'Oro\Bundle\ProductBundle\Entity\Product' => [
                'name' => 'names',
                'description' => 'descriptions',
                'shortDescription' => 'shortDescriptions',
                'slugPrototype' => 'slugPrototypes'
            ],
            'Oro\Bundle\ProductBundle\Entity\ProductKitItem' => [
                'label' => 'labels',
            ],
            'Oro\Bundle\ProductBundle\Entity\Brand' => [
                'name' => 'names',
                'description' => 'descriptions',
                'shortDescription' => 'shortDescriptions',
                'slugPrototype' => 'slugPrototypes'
            ],
        ]));
        $container->addCompilerPass(new ProductCollectionCompilerPass());
        $container->addCompilerPass(new AttributeBlockTypeMapperPass());
    }
}
