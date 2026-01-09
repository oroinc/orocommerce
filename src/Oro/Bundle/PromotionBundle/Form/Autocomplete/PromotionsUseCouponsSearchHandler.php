<?php

namespace Oro\Bundle\PromotionBundle\Form\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

/**
 * Searches for promotions that use coupons.
 */
class PromotionsUseCouponsSearchHandler extends SearchHandler
{
    #[\Override]
    protected function getEntitiesByIds(array $entityIds)
    {
        if (empty($entityIds)) {
            return [];
        }
        return $this->entityRepository->findBy([$this->idFieldName => $entityIds, 'useCoupons' => true]);
    }
}
