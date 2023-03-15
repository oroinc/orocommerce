<?php

namespace Oro\Bundle\PromotionBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Promotions aware entity configuration class.
 */
class PromotionAwareEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'promotion';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->booleanNode('is_promotion_aware')
                ->info('`boolean` promotion is aware flag.')
                ->defaultFalse()
            ->end()
            ->booleanNode('is_coupon_aware')
                ->info('`boolean` coupon is aware flag.')
                ->defaultFalse()
            ->end();
    }
}
