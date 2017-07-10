<?php

namespace Oro\Bundle\PromotionBundle\Placeholder;

use Oro\Bundle\OrderBundle\Entity\Order;

class OrderAdditionalPlaceholderFilter
{
    /**
     * @param mixed $entity
     * @return bool
     */
    public function isApplicable($entity)
    {
        return $entity instanceof Order && $entity->getId();
    }
}
