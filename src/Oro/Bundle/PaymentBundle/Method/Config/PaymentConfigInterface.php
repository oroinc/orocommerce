<?php

namespace Oro\Bundle\PaymentBundle\Method\Config;

interface PaymentConfigInterface
{
    /**
     * @return string
     */
    public function getLabel();

    /**
     * @return string
     */
    public function getShortLabel();
}
