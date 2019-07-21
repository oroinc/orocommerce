<?php

namespace Oro\Bundle\WebsiteSearchBundle\Api;

use Oro\Bundle\ApiBundle\Filter\SearchFieldResolver as BaseResolver;
use Oro\Bundle\ApiBundle\Filter\SearchFieldResolverFactory as BaseFactory;

/**
 * The factory to create SearchFieldResolver that can handle enum fields.
 */
class SearchFieldResolverFactory extends BaseFactory
{
    /**
     * {@inheritdoc}
     */
    public function createFieldResolver(string $entityClass, array $fieldMappings): BaseResolver
    {
        return new SearchFieldResolver(
            $this->getSearchFieldMappings($entityClass),
            $fieldMappings
        );
    }
}
