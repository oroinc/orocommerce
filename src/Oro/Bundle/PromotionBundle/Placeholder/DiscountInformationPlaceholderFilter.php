<?php

namespace Oro\Bundle\PromotionBundle\Placeholder;

use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;

/**
 * Filters discount information based on discount type.
 *
 * Determines whether discount information should be displayed for a promotion
 * by checking if the promotion's discount type matches the requested type.
 */
class DiscountInformationPlaceholderFilter
{
    /**
     * @param PromotionDataInterface $entity
     * @param string $type
     * @return bool
     */
    public function isApplicable(PromotionDataInterface $entity, $type)
    {
        return $entity->getDiscountConfiguration()->getType() === $type;
    }
}
