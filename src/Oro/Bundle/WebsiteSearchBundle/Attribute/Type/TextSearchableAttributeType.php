<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * Attribute type provides metadata for text attribute for search index.
 */
class TextSearchableAttributeType extends StringSearchableAttributeType
{
    /**
     * {@inheritdoc}
     */
    public function getSorterStorageFieldType(FieldConfigModel $attribute): string
    {
        throw new \RuntimeException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getSortableFieldName(FieldConfigModel $attribute): string
    {
        throw new \RuntimeException('Not supported');
    }
}
