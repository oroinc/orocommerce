<?php

namespace OroB2B\Bundle\ShippingBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ShippingMethodsCompilerPass implements CompilerPassInterface
{
    const TAG = 'orob2b_shipping_method';
    const SERVICE = 'orob2b_shipping.shipping_method.registry';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->getService())) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(
            $this->getTag()
        );
        if (!$taggedServices) {
            return;
        }

        $definition = $container->getDefinition(
            $this->getService()
        );
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addShippingMethod',
                [new Reference($id)]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getTag()
    {
        return self::TAG;
    }

    /**
     * {@inheritdoc}
     */
    protected function getService()
    {
        return self::SERVICE;
    }
}
