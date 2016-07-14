<?php

namespace OroB2B\Bundle\PaymentBundle\Method\View;

interface PaymentMethodViewInterface
{
    /**
     * @param array $context
     * @return array
     */
    public function getOptions(array $context = []);

    /**
     * @return string
     */
    public function getBlock();

    /**
     * @return int
     */
    public function getOrder();

    /**
     * @return string
     */
    public function getLabel();

    /**
     * @return string
     */
    public function getShortLabel();

    /**
     * @return string
     */
    public function getPaymentMethodType();
}
