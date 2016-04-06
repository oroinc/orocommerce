<?php

namespace OroB2B\Bundle\PaymentBundle\Method\View;

interface PaymentMethodViewInterface
{
    /**
     * @return array
     */
    public function getOptions();

    /**
     * @return string
     */
    public function getBlock();

    /**
     * @return int
     */
    public function getOrder();

    /**
     * @return int
     */
    public function getLabel();

    /**
     * @return string
     */
    public function getPaymentMethodType();
}
