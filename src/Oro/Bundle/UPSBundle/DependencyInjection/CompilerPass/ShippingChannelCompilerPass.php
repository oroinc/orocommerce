<?php

namespace Oro\Bundle\UPSBundle\DependencyInjection\CompilerPass;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\DependencyInjection\CompilerPass\ShippingMethodsCompilerPass;
use Oro\Bundle\UPSBundle\Method\UPS\UPSShippingMethod;
use Oro\Bundle\UPSBundle\Provider\ChannelType;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ShippingChannelCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $doctrine = $container->get('doctrine');

        /** @var Channel[] $channels */
        $channels = $doctrine->getManagerForClass('OroIntegrationBundle:Channel')
            ->getRepository('OroIntegrationBundle:Channel')->findBy([
                'type' => ChannelType::TYPE,
            ])
        ;
        foreach ($channels as $channel) {
            if ($channel->isEnabled()) {
                $definition = new Definition(UPSShippingMethod::class, [new Reference('doctrine'), $channel->getId()]);
                $definition->addTag(ShippingMethodsCompilerPass::TAG);
                $container->setDefinition('oro_ups.shipping_method.' . $channel->getId(), $definition);
            }
        }

        if (!$container->hasDefinition(ShippingMethodsCompilerPass::SERVICE)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(
            ShippingMethodsCompilerPass::TAG
        );

        $definition = $container->getDefinition(ShippingMethodsCompilerPass::SERVICE);
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addShippingMethod',
                [new Reference($id)]
            );
        }
    }
}
