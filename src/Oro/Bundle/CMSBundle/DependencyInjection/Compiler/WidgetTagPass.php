<?php

namespace Oro\Bundle\CMSBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;

class WidgetTagPass implements CompilerPassInterface
{
    const REGISTRY_SERVICE_ID = 'oro_cms.widget_registry';
    const TAG_NAME = 'oro_cms.widget';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $registry = $container->getDefinition(self::REGISTRY_SERVICE_ID);
        if ($registry) {
            $providers = $container->findTaggedServiceIds(self::TAG_NAME);

            $widgets = [];
            foreach ($providers as $id => $attributes) {
                $definition = $container->getDefinition($id);
                $definition->setPublic(false);

                foreach ($attributes as $eachTag) {
                    if (empty($eachTag['alias'])) {
                        throw new LogicException(sprintf('Widget alias isn\'t defined for "%s".', $id));
                    }
                    $widgets[$eachTag['alias']] = $definition;
                }
            }
            $registry->replaceArgument(1, $widgets);
        }
    }
}
