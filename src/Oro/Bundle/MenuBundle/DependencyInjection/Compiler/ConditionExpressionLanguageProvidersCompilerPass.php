<?php

namespace Oro\Bundle\MenuBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConditionExpressionLanguageProvidersCompilerPass implements CompilerPassInterface
{
    const TAG_NAME = 'orob2b_menu.condition.expression_language_provider';
    const CONDITION_SERVICE_ID = 'orob2b_menu.menu.condition.condition_extension';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $providers = $container->findTaggedServiceIds(self::TAG_NAME);

        $service = $container->getDefinition(self::CONDITION_SERVICE_ID);

        foreach ($providers as $providerId => $tags) {
            $service->addMethodCall('addProvider', [new Reference($providerId)]);
        }
    }
}
