<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Brand;

class BrandEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getName($format, $locale, $entity)
    {
        if ($format === EntityNameProviderInterface::FULL && is_a($entity, Brand::class)) {
            if ($locale instanceof Localization) {
                return (string)$entity->getName($locale);
            }

            return (string)$entity->getDefaultName();
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
