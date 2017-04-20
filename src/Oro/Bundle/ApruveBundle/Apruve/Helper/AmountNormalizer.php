<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Helper;

class AmountNormalizer
{
    /**
     * @param mixed $amount
     *
     * @return int
     */
    public static function normalize($amount)
    {
        $amountCents = ((float) $amount) * 100;

        return (int) round($amountCents, 0, PHP_ROUND_HALF_UP);
    }
}
