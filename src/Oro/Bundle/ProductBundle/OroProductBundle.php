<?php

namespace Oro\Bundle\ProductBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\AttributeBlockTypeMapperPass;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ComponentProcessorPass;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ProductCollectionCompilerPass;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ProductDataStorageSessionBagPass;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\TwigSandboxConfigurationPass;
use Oro\Bundle\ProductBundle\DependencyInjection\OroProductExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The ProductBundle bundle class.
 */
class OroProductBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroProductExtension();
        }

        return $this->extension;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ComponentProcessorPass());
        $container->addCompilerPass(new ProductDataStorageSessionBagPass());
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        $container->addCompilerPass(new DefaultFallbackExtensionPass([
            'Oro\Bundle\ProductBundle\Entity\Product' => [
                'name' => 'names',
                'description' => 'descriptions',
                'shortDescription' => 'shortDescriptions',
                'slugPrototype' => 'slugPrototypes'
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
