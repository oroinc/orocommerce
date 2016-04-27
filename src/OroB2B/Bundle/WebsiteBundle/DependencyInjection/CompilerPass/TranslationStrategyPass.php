<?php

namespace OroB2B\Bundle\WebsiteBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class TranslationStrategyPass implements CompilerPassInterface
{
    const STRATEGY_PROVIDER = 'oro_translation.strategy.provider';
    const COMPOSITE_STRATEGY = 'orob2b_website.translation.strategy.composite_fallback_strategy';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::STRATEGY_PROVIDER)) {
            $strategyProvider = $container->getDefinition(self::STRATEGY_PROVIDER);
            $strategyProvider->addMethodCall(
                'setStrategy',
                [$container->getDefinition(self::COMPOSITE_STRATEGY)]
            );
        }
    }
}
