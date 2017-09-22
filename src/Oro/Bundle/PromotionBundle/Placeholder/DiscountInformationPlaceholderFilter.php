<?php

namespace Oro\Bundle\PromotionBundle\Placeholder;

use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;

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
