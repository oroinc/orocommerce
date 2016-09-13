<?php

namespace Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ProductDataStorageSessionBagPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('session')) {
            return;
        }

        if (!$container->hasDefinition('oro_product.storage.product_data_bag')) {
            return;
        }

        $container->getDefinition('session')->addMethodCall(
            'registerBag',
            [new Reference('oro_product.storage.product_data_bag')]
        );
    }
}
