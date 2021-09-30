<?php

namespace Oro\Bundle\WebsiteSearchBundle\Config\Validation;

use Oro\Bundle\EntityConfigBundle\Config\Validation\FieldConfigInterface;
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
            ->end()
        ;
    }
}
