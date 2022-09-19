<?php

namespace Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorTrait;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all checkout line item converters.
 */
class CheckoutLineItemConverterPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $tagName = 'oro.checkout.line_item.converter';
        $items = [];
        $taggedServices = $container->findTaggedServiceIds($tagName, true);
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $items[$this->getPriorityAttribute($attributes)][] = [
                    $this->getRequiredAttribute($attributes, 'alias', $id, $tagName),
                    $id
                ];
            }
        }

        $services = [];
        if ($items) {
            ksort($items);
            $items = array_merge(...array_values($items));
            foreach ($items as [$key, $id]) {
                if (!isset($services[$key])) {
                    $services[$key] = new Reference($id);
                }
            }
        }

        $container->getDefinition('oro_checkout.line_item.converter_registry')
            ->setArgument('$converters', new IteratorArgument(array_values($services)));
    }
}
