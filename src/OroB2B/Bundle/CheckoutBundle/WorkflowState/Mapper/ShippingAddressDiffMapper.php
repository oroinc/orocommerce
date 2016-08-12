<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class ShippingAddressDiffMapper extends AbstractAddressDiffMapper
{
    const DATA_NAME = 'shippingAddress';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::DATA_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getAddress(Checkout $checkout)
    {
        return $checkout->getShippingAddress();
    }
}
