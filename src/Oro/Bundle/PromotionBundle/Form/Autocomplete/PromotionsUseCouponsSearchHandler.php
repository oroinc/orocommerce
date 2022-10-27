<?php

namespace Oro\Bundle\PromotionBundle\Form\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

class PromotionsUseCouponsSearchHandler extends SearchHandler
{
    /**
     * {@inheritdoc}
     */
    protected function getEntitiesByIds(array $entityIds)
    {
        return $this->entityRepository->findBy([$this->idFieldName => $entityIds, 'useCoupons' => true]);
    }
}
