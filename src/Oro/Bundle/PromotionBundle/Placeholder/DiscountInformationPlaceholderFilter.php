<?php

namespace Oro\Bundle\PromotionBundle\Placeholder;

use Oro\Bundle\PromotionBundle\Entity\Promotion;

class DiscountInformationPlaceholderFilter
{
    /**
     * @param Promotion $entity
     * @param string $type
     * @return bool
     */
    public function isApplicable(Promotion $entity, $type)
    {
        return $entity->getDiscountConfiguration()->getType() === $type;
    }
}
