<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\CurrencyBundle\Entity\Price;

// TODO: Discount information should contain information about promotion (source) for example it's labels (name?)
// TODO -> are required for rendering data on frontend
class DiscountInformation
{
    const TYPE_FIXED_AMOUNT = 'fixed';
    const TYPE_PERCENT = 'percent';

    /**
     * self::TYPE_FIXED_AMOUNT or self::TYPE_PERCENT
     *
     * @return string
     */
    public function getType()
    {

    }

    /**
     * @return Price
     */
    public function getValue()
    {

    }

    /**
     * @return float
     */
    public function getPercentage()
    {

    }
}
