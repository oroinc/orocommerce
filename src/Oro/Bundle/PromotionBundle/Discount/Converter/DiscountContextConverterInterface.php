<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\PromotionBundle\Discount\DiscountContext;

interface DiscountContextConverterInterface
{
    /**
     * @param object $sourceEntity
     * @return DiscountContext
     */
    public function convert($sourceEntity): DiscountContext;

    /**
     * @param object $sourceEntity
     * @return bool
     */
    public function supports($sourceEntity): bool;
}
