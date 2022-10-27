<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;

/**
 * Represents ProductPrice entities by 'name' field avoiding usage of FullNameInterface.
 */
class ProductPriceEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritdoc}
     *
     * @param ProductPrice $entity
     */
    public function getName($format, $locale, $entity)
    {
        if ($format === EntityNameProviderInterface::FULL && is_a($entity, ProductPrice::class)) {
            $result = [];

            if ($entity->getProductSku()) {
                $result[] = $entity->getProductSku();
            }

            $unit = $entity->getProductUnit() ? $entity->getProductUnit()->getCode() : '';
            $value = trim($entity->getQuantity() . ' ' . $unit);
            if ($value) {
                $result[] = $value;
            }

            $price = $entity->getPrice();
            if ($price) {
                $result[] = $price->getValue() . ' ' . $price->getCurrency();
            }

            return implode(' | ', $result);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        return false;
    }
}
