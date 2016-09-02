<?php

namespace Oro\Bundle\CatalogBundle\Fallback\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\InvalidFallbackArgumentException;
use Oro\Bundle\EntityBundle\Fallback\Provider\AbstractEntityFallbackProvider;

class ParentCategoryFallbackProvider extends AbstractEntityFallbackProvider
{
    const ID = 'parentCategory';

    /**
     * {@inheritdoc}
     */
    public function getFallbackHolderEntity(
        $object,
        $objectFieldName,
        EntityFieldFallbackValue $objectFallbackValue,
        $fallbackConfig
    ) {
        if (!$object instanceof Category) {
            throw new InvalidFallbackArgumentException(get_class($object), get_class($this));
        }

        return $object->getParentCategory();
    }
}
