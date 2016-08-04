<?php

namespace OroB2B\Bundle\PaymentBundle\Method\Config;

interface PaymentConfigInterface
{
    /**
     * @return bool
     */
    public function isEnabled();

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
}
