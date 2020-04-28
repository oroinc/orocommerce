<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;

/**
 * Provide title for Coupon entity.
 */
class CouponEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof Coupon) {
            return false;
        }

        return $entity->getCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, Coupon::class, true)) {
            return false;
        }

        return sprintf('%s.code', $alias);
    }
}
