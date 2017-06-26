<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\PromotionBundle\Discount\DiscountContext;

interface DiscountContextConverterInterface extends ConverterInterface
{
    /**
     * @param object $sourceEntity
     * @return DiscountContext
     */
    public function convert($sourceEntity): DiscountContext;
}
