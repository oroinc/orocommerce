<?php

namespace Oro\Bundle\CatalogBundle\Fallback\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\Exception\Fallback\InvalidFallbackArgumentException;
use Oro\Bundle\EntityBundle\Fallback\Provider\AbstractEntityFallbackProvider;

class ParentCategoryFallbackProvider extends AbstractEntityFallbackProvider
{
    const FALLBACK_ID = 'parentCategory';

    #[\Override]
    public function getFallbackHolderEntity($object, $objectFieldName)
    {
        if (!$object instanceof Category) {
            throw new InvalidFallbackArgumentException(get_class($object), get_class($this));
        }

        return $object->getParentCategory();
    }

    #[\Override]
    public function isFallbackSupported($object, $fieldName)
    {
        if (!$object instanceof Category || !$object->getParentCategory()) {
            return false;
        }

        return true;
    }

    #[\Override]
    public function getFallbackLabel()
    {
        return 'oro.catalog.fallback.parent_category.label';
    }

    #[\Override]
    public function getFallbackEntityClass()
    {
        return Category::class;
    }
}
