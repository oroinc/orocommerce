<?php

namespace Oro\Bundle\PromotionBundle\Form\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

/**
 * Autocomplete search handler for promotions that use coupons.
 *
 * Filters promotions by the useCoupons flag, returning only promotions that support coupon-based discounts.
 */
class PromotionsUseCouponsSearchHandler extends SearchHandler
{
    #[\Override]
    protected function getEntitiesByIds(array $entityIds)
    {
        return $this->entityRepository->findBy([$this->idFieldName => $entityIds, 'useCoupons' => true]);
    }
}
