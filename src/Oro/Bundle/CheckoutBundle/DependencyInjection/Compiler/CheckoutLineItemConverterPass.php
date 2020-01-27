<?php

namespace Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorTrait;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all checkout line item converters.
 */
class CheckoutLineItemConverterPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = $this->findAndInverseSortTaggedServices(
            'oro.checkout.line_item.converter',
            'alias',
            $container
        );

        $container->getDefinition('oro_checkout.line_item.converter_registry')
            ->setArgument(0, new IteratorArgument(array_values($services)));
    }
}
