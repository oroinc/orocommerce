<?php

namespace OroB2B\Bundle\TaxBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ResolverEventConnectorPass implements CompilerPassInterface
{
    const TAG_NAME = 'orob2b_tax.resolver';
    const CONNECTOR_CLASS = 'orob2b_tax.event.resolver_event_connector.class';
    const CONNECTOR_SERVICE_NAME = 'orob2b_tax.event.resolver_event_connector';

    /** {@inheritdoc} */
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);

        foreach ($taggedServices as $id => $tags) {
            $tag = reset($tags);
            if (empty($tag['alias']) || empty($tag['event'])) {
                throw new \InvalidArgumentException(sprintf('Wrong tags configuration "%s"', json_encode($tags)));
            }

            $definition = new Definition($container->getParameter(self::CONNECTOR_CLASS), [new Reference($id)]);
            $definition->addTag('kernel.event_listener', ['event' => $tag['event'], 'method' => 'onResolve']);
            $container->setDefinition(sprintf('%s.%s', self::CONNECTOR_SERVICE_NAME, $tag['alias']), $definition);
        }
    }
}
