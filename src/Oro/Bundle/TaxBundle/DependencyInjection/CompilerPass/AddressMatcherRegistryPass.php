<?php
namespace Oro\Bundle\TaxBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddressMatcherRegistryPass implements CompilerPassInterface
{
    const TAG = 'oro_tax.address_matcher';
    const SERVICE = 'oro_tax.address_matcher_registry';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(static::SERVICE)) {
            return;
        }

        $services = $container->findTaggedServiceIds(self::TAG);
        if (empty($services)) {
            return;
        }

        $registryDefinition = $container->getDefinition(static::SERVICE);

        foreach ($services as $id => $tags) {
            foreach ($tags as $attributes) {
                if (!array_key_exists('type', $attributes)) {
                    throw new \InvalidArgumentException(
                        sprintf('Attribute "type" is missing for "%s" tag at "%s" service', self::TAG, $id)
                    );
                }

                $reference = new Reference($id);
                $registryDefinition->addMethodCall('addMatcher', [$attributes['type'], $reference]);
            }
        }
    }
}
