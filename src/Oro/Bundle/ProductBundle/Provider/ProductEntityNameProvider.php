<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * If product has localization name and default name it will return the localized name.
 * If product has the default name and there is no localized name it will return the default name.
 * In other cases return sku.
 */
class ProductEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getName($format, $locale, $entity)
    {
        if ($format === EntityNameProviderInterface::FULL && is_a($entity, Product::class)) {
            if ($locale instanceof Localization) {
                $name = (string)$entity->getName($locale);
            }

            return $name ?? (string)$entity;
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
