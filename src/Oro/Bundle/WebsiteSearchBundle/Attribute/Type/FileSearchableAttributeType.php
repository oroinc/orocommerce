<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * Attribute type provides metadata for file attribute for search index.
 */
class FileSearchableAttributeType extends AbstractSearchableAttributeType
{
    #[\Override]
    public function getFilterStorageFieldTypes(FieldConfigModel $attribute): array
    {
        throw new \RuntimeException('Not supported');
    }

    #[\Override]
    public function getSorterStorageFieldType(FieldConfigModel $attribute): string
    {
        throw new \RuntimeException('Not supported');
    }

    #[\Override]
    public function getFilterType(FieldConfigModel $attribute): string
    {
        throw new \RuntimeException('Not supported');
    }

    #[\Override]
    public function isLocalizable(FieldConfigModel $attribute): bool
    {
        return false;
    }

    #[\Override]
    public function getFilterableFieldNames(FieldConfigModel $attribute): array
    {
        throw new \RuntimeException('Not supported');
    }

    #[\Override]
    public function getSortableFieldName(FieldConfigModel $attribute): string
    {
        throw new \RuntimeException('Not supported');
    }

    #[\Override]
    public function getSearchableFieldName(FieldConfigModel $attribute): string
    {
        throw new \RuntimeException('Not supported');
    }
}
