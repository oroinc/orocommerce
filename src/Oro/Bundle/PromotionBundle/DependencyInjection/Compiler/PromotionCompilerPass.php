<?php

namespace Oro\Bundle\PromotionBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PromotionCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;
    
    const DISCOUNT_CONTEXT_CONVERTER_REGISTRY = 'oro_promotion.discount.context_converter.registry';
    const DISCOUNT_CONTEXT_CONVERTER_TAG = 'oro_promotion.discount_context_converter';

    const PROMOTION_CONTEXT_DATA_CONVERTER_REGISTRY = 'oro_promotion.promotion.context_data_converter_registry';
    const PROMOTION_CONTEXT_DATA_CONVERTER_TAG = 'oro_promotion.promotion_context_converter';

    const DISCOUNT_STRATEGY_REGISTRY = 'oro_promotion.discount.strategy_registry';
    const DISCOUNT_STRATEGY_TAG = 'oro_promotion.discount_strategy';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices(
            $container,
            self::DISCOUNT_CONTEXT_CONVERTER_REGISTRY,
            self::DISCOUNT_CONTEXT_CONVERTER_TAG,
            'registerConverter'
        );

        $this->registerTaggedServices(
            $container,
            self::PROMOTION_CONTEXT_DATA_CONVERTER_REGISTRY,
            self::PROMOTION_CONTEXT_DATA_CONVERTER_TAG,
            'registerConverter'
        );

        $this->registerTaggedServices(
            $container,
            self::DISCOUNT_STRATEGY_REGISTRY,
            self::DISCOUNT_STRATEGY_TAG,
            'addStrategy'
        );
    }
}
