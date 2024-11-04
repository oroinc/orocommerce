<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;

/**
 * Provides a text representation of ProductPrice entity.
 */
class ProductPriceEntityNameProvider implements EntityNameProviderInterface
{
    #[\Override]
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof ProductPrice) {
            return false;
        }

        if (self::SHORT === $format) {
            return $entity->getProductSku();
        }

        return implode(' | ', [
            $entity->getProductSku(),
            $entity->getQuantity() . ' ' . $entity->getProductUnit()->getCode(),
            $entity->getPrice()->getValue() . ' ' . $entity->getPrice()->getCurrency()
        ]);
    }

    #[\Override]
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, ProductPrice::class, true)) {
            return false;
        }

        if (self::SHORT === $format) {
            return $alias . '.productSku';
        }

        return sprintf(
            '(SELECT CONCAT(%1$s_p.productSku, \' | \','
            . ' CAST(%1$s_p.quantity AS string), \' \', %1$s_u.code, \' | \','
            . ' TRIM(TRAILING \'.\' FROM TRIM(TRAILING \'0\' FROM CAST(%1$s_p.value AS string))),'
            . ' \' \', %1$s_p.currency) FROM %2$s %1$s_p INNER JOIN %1$s_p.unit %1$s_u WHERE %1$s_p = %1$s)',
            $alias,
            ProductPrice::class
        );
    }
}
