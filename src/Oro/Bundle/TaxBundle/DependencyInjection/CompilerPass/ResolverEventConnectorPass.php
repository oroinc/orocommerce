<?php

namespace Oro\Bundle\TaxBundle\DependencyInjection\CompilerPass;

use Oro\Bundle\TaxBundle\Event\ResolverEventConnector;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures event connectors for tax resolvers.
 */
class ResolverEventConnectorPass implements CompilerPassInterface
{
    const TAG_NAME = 'oro_tax.resolver';
    const CONNECTOR_CLASS = 'oro_tax.event.resolver_event_connector.common_class';
    const CONNECTOR_SERVICE_NAME_SUFFIX = 'event.resolver_event_connector';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        $connectorClass = $this->getConnectorClass($container);
        $taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);
        foreach ($taggedServices as $id => $tags) {
            if (!$tags) {
                continue;
            }

            $definition = new Definition($connectorClass, [new Reference($id)]);
            foreach ($tags as $tag) {
                if (empty($tag['event'])) {
                    throw new \InvalidArgumentException(sprintf('Wrong tags configuration "%s"', json_encode($tags)));
                }

                $attributes = ['event' => $tag['event'], 'method' => 'onResolve'];
                if (!empty($tag['priority'])) {
                    $attributes['priority'] = $tag['priority'];
                }
                $definition->addTag('kernel.event_listener', $attributes);
            }

            $container->setDefinition(sprintf('%s.%s', $id, self::CONNECTOR_SERVICE_NAME_SUFFIX), $definition);
        }
    }

    private function getConnectorClass(ContainerBuilder $container): string
    {
        if ($container->hasParameter(self::CONNECTOR_CLASS)) {
            return $container->getParameter(self::CONNECTOR_CLASS);
        }

        return ResolverEventConnector::class;
    }
}
