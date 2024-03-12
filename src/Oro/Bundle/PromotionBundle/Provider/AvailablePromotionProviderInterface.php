<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\PromotionBundle\Entity\Promotion;

/**
 * Represents a service that provides information about promotions available for a specific context.
 */
interface AvailablePromotionProviderInterface
{
    /**
     * @param array  $contextData
     *
     * @return Promotion[]
     */
    public function getAvailablePromotions(array $contextData): array;
}
