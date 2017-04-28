<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Helper;

interface AmountNormalizerInterface
{
    /**
     * @param string|int|float $amount
     *
     * @return int
     */
    public function normalize($amount);
}
