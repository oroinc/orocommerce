<?php

namespace Oro\Bundle\WebsiteSearchBundle\Api;

use Oro\Bundle\SearchBundle\Api\Filter\SearchFieldResolver as BaseResolver;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;

/**
 * Adds handling of enum fields to the base resolver.
 */
class SearchFieldResolver extends BaseResolver
{
    /**
     * {@inheritdoc}
     */
    protected function guessFieldNames(string $fieldName): array
    {
        return array_merge(
            parent::guessFieldNames($fieldName),
            [$fieldName . '_enum.' . EnumIdPlaceholder::NAME]
        );
    }
}
