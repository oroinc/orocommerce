<?php

namespace Oro\Bundle\ScopeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ScopeProviderCompiler implements CompilerPassInterface
{
    const SCOPE_MANAGER = 'oro_scope.manager.scope_manager';
    const SCOPE_PROVIDER_TAG = 'oro_scope.provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::SCOPE_MANAGER)) {
            return;
        }
        $definition = $container->getDefinition(self::SCOPE_MANAGER);
        $taggedServices = $container->findTaggedServiceIds(self::SCOPE_PROVIDER_TAG);
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $tag) {
                $definition->addMethodCall(
                    'addProvider',
                    [$tag['scopeType'], new Reference($id)]
                );
            }
        }
    }
}
