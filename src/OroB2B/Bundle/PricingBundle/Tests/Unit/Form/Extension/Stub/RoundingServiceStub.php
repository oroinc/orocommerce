<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;

class RoundingServiceStub implements RoundingServiceInterface
{

    /**
     * {@inheritdoc}
     */
    public function round($value, $precision = null, $roundType = null)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrecision()
    {
        return 4;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoundType()
    {
        return RoundingServiceInterface::ROUND_HALF_UP;
    }
}
