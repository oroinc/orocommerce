<?php

namespace Oro\Bundle\PaymentBundle\Method\View;

interface PaymentMethodViewInterface
{
    /**
     * @return array
     * @internal param array $context
     */
    public function getOptions();

    /**
     * @return string
     */
    public function getBlock();

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
