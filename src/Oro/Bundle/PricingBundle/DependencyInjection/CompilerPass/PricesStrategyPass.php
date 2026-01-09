<?php

namespace Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that registers price combining strategies in the strategy register.
 *
 * Collects all services tagged with `oro_pricing.price_strategy` and registers them
 * in the StrategyRegister service, making them available for price calculation operations.
 */
class PricesStrategyPass implements CompilerPassInterface
{
    public const STRATEGY_REGISTER = 'oro_pricing.pricing_strategy.strategy_register';
    public const STRATEGY_ALIAS = 'alias';
    public const STRATEGY_TAG = 'oro_pricing.price_strategy';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::STRATEGY_REGISTER)) {
            return;
        }
        $taggedServices = $container->findTaggedServiceIds(self::STRATEGY_TAG);
        if (empty($taggedServices)) {
            return;
        }
        $registryDefinition = $container->getDefinition(self::STRATEGY_REGISTER);
        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                if (!array_key_exists(self::STRATEGY_ALIAS, $attributes)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Attribute "%s" is missing for "%s" tag at "%s" service',
                            self::STRATEGY_ALIAS,
                            self::STRATEGY_TAG,
                            $serviceId
                        )
                    );
                }
                $registryDefinition->addMethodCall(
                    'add',
                    [$attributes[self::STRATEGY_ALIAS],
                        new Reference($serviceId)]
                );
            }
        }
    }
}
