<?php

namespace Oro\Bundle\RuleBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers expression language functions with the rule expression language service.
 *
 * This compiler pass is executed during the dependency injection container compilation phase.
 * It discovers all services tagged with 'oro_rule.expression_language.function' and registers them
 * with the main expression language service. This allows bundles to extend the rule engine
 * with custom expression functions by simply tagging their function services, enabling a plugin-like architecture
 * for adding domain-specific functions to rule expressions.
 */
class ExpressionLanguageFunctionCompilerPass implements CompilerPassInterface
{
    const EXPRESSION_LANGUAGE_SERVICE = 'oro_rule.expression_language';
    const TAG = 'oro_rule.expression_language.function';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::EXPRESSION_LANGUAGE_SERVICE)) {
            return;
        }

        $definition = $container->getDefinition(self::EXPRESSION_LANGUAGE_SERVICE);
        $taggedServices = $container->findTaggedServiceIds(self::TAG);

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addFunction', [new Reference($id)]);
        }
    }
}
