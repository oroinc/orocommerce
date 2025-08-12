<?php

namespace Oro\Bundle\WebsiteSearchBundle\Api\Filter;

use Oro\Bundle\SearchBundle\Api\Filter\SearchFieldResolver as BaseResolver;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;

/**
 * Implements mapping between field names and types in a search expression and website search index.
 */
class SearchFieldResolver extends BaseResolver
{
    private bool $supportEnums;

    public function __construct(array $searchFieldMappings, array $fieldMappings, bool $supportEnums)
    {
        parent::__construct($searchFieldMappings, $fieldMappings);
        $this->supportEnums = $supportEnums;
    }

    #[\Override]
    protected function guessFieldNames(string $fieldName): array
    {
        $guessedFieldNames = parent::guessFieldNames($fieldName);
        if ($this->supportEnums) {
            $guessedFieldNames = array_merge(
                $guessedFieldNames,
                [$fieldName . '_enum.' . EnumIdPlaceholder::NAME]
            );
        }

        return $guessedFieldNames;
    }
}
