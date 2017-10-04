<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class TextSearchableAttributeType extends StringSearchableAttributeType
{
    /**
     * {@inheritdoc}
     */
    public function getSorterStorageFieldType()
    {
        throw new \RuntimeException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getSortableFieldName(FieldConfigModel $attribute)
    {
        throw new \RuntimeException('Not supported');
    }
}
