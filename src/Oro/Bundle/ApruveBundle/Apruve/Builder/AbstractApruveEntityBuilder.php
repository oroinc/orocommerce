<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder;

use Oro\Bundle\ApruveBundle\Apruve\Helper\AmountNormalizer;
use Oro\Bundle\CurrencyBundle\Entity\Price;

abstract class AbstractApruveEntityBuilder
{
    /**
     * @param Price $price
     *
     * @return int
     */
    protected function normalizePrice(Price $price)
    {
        return $this->normalizeAmount($price->getValue());
    }

    /**
     * @param float|int|string $amount
     *
     * @return int
     */
    protected function normalizeAmount($amount)
    {
        return (int) AmountNormalizer::normalize($amount);
    }
}
