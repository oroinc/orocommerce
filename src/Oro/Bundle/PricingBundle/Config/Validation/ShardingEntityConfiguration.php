<?php

namespace Oro\Bundle\PricingBundle\Config\Validation;

use Oro\Bundle\EntityConfigBundle\Config\Validation\EntityConfigInterface;
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
            ->booleanNode('discrimination_field')
                ->info('`string` is the name of the sharding field. Example: “priceList”.')
            ->end()
        ;
    }
}
