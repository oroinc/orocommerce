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

        if (!$container->hasDefinition('orob2b_product.storage.product_data_bag')) {
            return;
        }

        $container->getDefinition('session')->addMethodCall(
            'registerBag',
            [new Reference('orob2b_product.storage.product_data_bag')]
        );
    }
}
