<?php

namespace Oro\Bundle\RedirectBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for slug scope.
 */
class SlugEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'slug';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('source')
                ->info('`string` slug source field name.')
                ->defaultNull()
            ->end()
        ;
    }
}
