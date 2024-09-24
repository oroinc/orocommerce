<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;

/**
 * Provides a text representation of Product entity.
 */
class ProductEntityNameProvider implements EntityNameProviderInterface
{
    #[\Override]
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof Product || self::FULL !== $format) {
            return false;
        }

        $localizedName = $locale instanceof Localization
            ? (string)$entity->getName($locale)
            : null;

        return $localizedName ?: (string)$entity->getDefaultName() ?: $entity->getSku();
    }

    #[\Override]
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, Product::class, true) || self::FULL !== $format) {
            return false;
        }

        if ($locale instanceof Localization) {
            return sprintf(
                'CAST((SELECT COALESCE(%1$s_n.string, NULLIF(%1$s_dn.string, \'\'), %1$s.sku) FROM %2$s %1$s_dn'
                . ' LEFT JOIN %2$s %1$s_n WITH %1$s_n MEMBER OF %1$s.names AND %1$s_n.localization = %3$s'
                . ' WHERE %1$s_dn MEMBER OF %1$s.names AND %1$s_dn.localization IS NULL) AS string)',
                $alias,
                ProductName::class,
                $locale->getId()
            );
        }

        return sprintf(
            'CAST((SELECT COALESCE(NULLIF(%1$s_n.string, \'\'), %1$s.sku) FROM %2$s %1$s_n'
            . ' WHERE %1$s_n MEMBER OF %1$s.names AND %1$s_n.localization IS NULL) AS string)',
            $alias,
            ProductName::class
        );
    }
}
