<?php

namespace Oro\Bundle\TaxBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TaxMapperPass implements CompilerPassInterface
{
    const REGISTRY_SERVICE = 'oro_tax.factory.tax';
    const TAG = 'orob2b_tax.tax_mapper';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::REGISTRY_SERVICE)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        if (empty($taggedServices)) {
            return;
        }

        $registryDefinition = $container->getDefinition(self::REGISTRY_SERVICE);

        foreach (array_keys($taggedServices) as $id) {
            $registryDefinition->addMethodCall('addMapper', [new Reference($id)]);
        }
    }
}
