<?php

namespace Oro\Bundle\PricingBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for sharding scope
 */
class ShardingEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'sharding';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('discrimination_field')
                ->info('`string` is the name of the sharding field.')
                ->example('priceList')
                ->defaultFalse()
            ->end()
        ;
    }
}
