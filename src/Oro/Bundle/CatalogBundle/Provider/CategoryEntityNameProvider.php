<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Provides a text representation of Category entity.
 */
class CategoryEntityNameProvider implements EntityNameProviderInterface
{
    #[\Override]
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof Category) {
            return false;
        }

        $localizedTitle = $locale instanceof Localization
            ? (string)$entity->getTitle($locale)
            : null;

        return $localizedTitle ?: (string)$entity->getDefaultTitle();
    }

    #[\Override]
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, Category::class, true)) {
            return false;
        }

        if ($locale instanceof Localization) {
            return sprintf(
                'CAST((SELECT COALESCE(%1$s_t.string, %1$s_dt.string) FROM %2$s %1$s_dt'
                . ' LEFT JOIN %2$s %1$s_t WITH %1$s_t MEMBER OF %1$s.titles AND %1$s_t.localization = %3$s'
                . ' WHERE %1$s_dt MEMBER OF %1$s.titles AND %1$s_dt.localization IS NULL) AS string)',
                $alias,
                CategoryTitle::class,
                $locale->getId()
            );
        }

        return sprintf(
            'CAST((SELECT %1$s_t.string FROM %2$s %1$s_t'
            . ' WHERE %1$s_t MEMBER OF %1$s.titles AND %1$s_t.localization IS NULL) AS string)',
            $alias,
            CategoryTitle::class
        );
    }
}
