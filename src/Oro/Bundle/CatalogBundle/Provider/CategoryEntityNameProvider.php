<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;

class CategoryEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritDoc}
     *
     * @param Category $entity
     */
    public function getName($format, $locale, $entity)
    {
        if ($format === EntityNameProviderInterface::FULL && is_a($entity, Category::class)) {
            if ($locale instanceof Localization) {
                return (string)$entity->getTitle($locale);
            }

            return (string)$entity->getDefaultTitle();
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        return false;
    }
}
