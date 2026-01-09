<?php

namespace Oro\Bundle\PaymentBundle\Model;

/**
 * Represents surcharge amounts for payment processing.
 *
 * This model holds various surcharge amounts including shipping, handling, discount,
 * and insurance, which are collected during the payment process and sent to payment gateways.
 */
class Surcharge
{
    /** @var float */
    protected $shippingAmount = 0.;

    /** @var float */
    protected $handlingAmount = 0.;

    /** @var float */
    protected $discountAmount = 0.;

    /** @var float */
    protected $insuranceAmount = 0.;

    /**
     * @param float $amount
     * @return $this
     */
    public function setShippingAmount($amount)
    {
        $this->shippingAmount = (float)$amount;

        return $this;
    }

    /**
     * @return float
     */
    public function getShippingAmount()
    {
        return $this->shippingAmount;
    }

    /**
     * @param float $amount
     * @return $this
     */
    public function setHandlingAmount($amount)
    {
        $this->handlingAmount = (float)$amount;

        return $this;
    }

    /**
     * @return float
     */
    public function getHandlingAmount()
    {
        return $this->handlingAmount;
    }

    /**
     * @param float $amount
     * @return $this
     */
    public function setDiscountAmount($amount)
    {
        $this->discountAmount = (float)$amount;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountAmount()
    {
        return $this->discountAmount;
    }

    /**
     * @param float $amount
     * @return $this
     */
    public function setInsuranceAmount($amount)
    {
        $this->insuranceAmount = (float)$amount;

        return $this;
    }

    /**
     * @return float
     */
    public function getInsuranceAmount()
    {
        return $this->insuranceAmount;
    }
}
