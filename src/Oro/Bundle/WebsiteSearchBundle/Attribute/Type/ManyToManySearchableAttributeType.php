<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

/**
 * Attribute type provides metadata for manyToMany attribute for search index.
 */
class ManyToManySearchableAttributeType extends AbstractSearchableAttributeType
{
    /**
     * {@inheritdoc}
     */
    protected function getFilterStorageFieldTypeMain(): string
    {
        return Query::TYPE_TEXT;
    }

    /**
     * {@inheritdoc}
     */
    public function getSorterStorageFieldType(): string
    {
        return Query::TYPE_TEXT;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterType(): string
    {
        return self::FILTER_TYPE_STRING;
    }

    /**
     * {@inheritdoc}
     */
    public function isLocalizable(FieldConfigModel $attribute): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFilterableFieldNameMain(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName() . '_' . LocalizationIdPlaceholder::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortableFieldName(FieldConfigModel $attribute): string
    {
        return $this->getFilterableFieldNameMain($attribute);
    }
}
