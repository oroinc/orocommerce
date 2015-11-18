<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class ConditionPass implements CompilerPassInterface
{
    const EXPRESSION_TAG = 'oro_action.condition';
    const EXTENSION_SERVICE_ID = 'oro_action.expression.extension';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::EXTENSION_SERVICE_ID)) {
            $types = [];

            $conditions = $container->findTaggedServiceIds(self::EXPRESSION_TAG);

            foreach ($conditions as $id => $attributes) {
                $definition = $container->getDefinition($id);
                $definition->setScope(ContainerInterface::SCOPE_PROTOTYPE)->setPublic(false);

                foreach ($attributes as $eachTag) {
                    $aliases = empty($eachTag['alias']) ? [$id] : explode('|', $eachTag['alias']);

                    foreach ($aliases as $alias) {
                        $types[$alias] = $id;
                    }
                }
            }

            $extensionDef = $container->getDefinition(self::EXTENSION_SERVICE_ID);
            $extensionDef->replaceArgument(1, $types);
        }
    }
}
