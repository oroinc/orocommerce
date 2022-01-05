<?php

namespace Oro\Bundle\WebsiteSearchBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for attribute scope.
 */
class AttributeFieldConfiguration implements FieldConfigInterface
{
    public function getSectionName(): string
    {
        return 'attribute';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('search_boost')
                ->info('`integer` enables you to influence the relevancy ranking of the search results by the ' .
                'value of the attributes.')
                ->validate()
                    ->ifTrue(static fn ($value) => $value !== null && (!is_numeric($value) || $value < 0))
                    ->thenInvalid('must be a valid positive number, got %s.')
            ->end()
        ;
    }
}
