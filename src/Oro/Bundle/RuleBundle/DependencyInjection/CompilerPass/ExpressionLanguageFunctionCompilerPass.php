<?php

namespace Oro\Bundle\RuleBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ExpressionLanguageFunctionCompilerPass implements CompilerPassInterface
{
    const EXPRESSION_LANGUAGE_SERVICE = 'oro_rule.expression_language';
    const TAG = 'oro_rule.expression_language.function';

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
