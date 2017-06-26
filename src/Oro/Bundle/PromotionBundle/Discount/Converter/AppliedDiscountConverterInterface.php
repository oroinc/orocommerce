<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;

interface AppliedDiscountConverterInterface extends ConverterInterface
{
    public function convert($sourceEntity): AppliedDiscount;
}
