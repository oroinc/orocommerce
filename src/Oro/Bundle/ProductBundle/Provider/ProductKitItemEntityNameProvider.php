<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;

/**
 * Provides a text representation of ProductKitItem entity.
 */
class ProductKitItemEntityNameProvider implements EntityNameProviderInterface
{
    #[\Override]
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof ProductKitItem) {
            return false;
        }

        $localizedLabel = $locale instanceof Localization
            ? (string)$entity->getLabel($locale)
            : null;

        return $localizedLabel ?: (string)$entity->getDefaultLabel();
    }

    #[\Override]
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, ProductKitItem::class, true)) {
            return false;
        }

        if ($locale instanceof Localization) {
            return sprintf(
                'CAST((SELECT COALESCE(%1$s_l.string, %1$s_dl.string) FROM %2$s %1$s_dl'
                . ' LEFT JOIN %2$s %1$s_l WITH %1$s_l MEMBER OF %1$s.labels AND %1$s_l.localization = %3$s'
                . ' WHERE %1$s_dl MEMBER OF %1$s.labels AND %1$s_dl.localization IS NULL) AS string)',
                $alias,
                ProductKitItemLabel::class,
                $locale->getId()
            );
        }

        return sprintf(
            'CAST((SELECT %1$s_l.string FROM %2$s %1$s_l'
            . ' WHERE %1$s_l MEMBER OF %1$s.labels AND %1$s_l.localization IS NULL) AS string)',
            $alias,
            ProductKitItemLabel::class
        );
    }
}
