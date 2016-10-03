<?php

namespace Oro\Bundle\ProductBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ComponentProcessorPass;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\DefaultProductUnitProvidersCompilerPass;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ProductDataStorageSessionBagPass;
use Oro\Bundle\ProductBundle\DependencyInjection\OroProductExtension;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\TwigSandboxConfigurationPass;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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

        $container
            ->addCompilerPass(new ComponentProcessorPass())
            ->addCompilerPass(new ProductDataStorageSessionBagPass())
            ->addCompilerPass(new TwigSandboxConfigurationPass())
            ->addCompilerPass(new DefaultProductUnitProvidersCompilerPass())
            ->addCompilerPass(new DefaultFallbackExtensionPass([
                Product::class => [
                    'name' => 'names',
                    'description' => 'descriptions',
                    'shortDescription' => 'shortDescriptions'
                ],
            ]))
        ;
    }
}
