<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class BillingAddressDiffMapper extends AbstractAddressDiffMapper
{
    const DATA_NAME = 'billingAddress';

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
        return $checkout->getBillingAddress();
    }
}
