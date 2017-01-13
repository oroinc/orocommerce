<?php

namespace Oro\Bundle\DPDBundle\Context;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

interface DPDShippingContextInterface extends ShippingContextInterface
{
    /**
     * @return \DateTime
     */
    public function getShipDate();

    public function getEmail();

    public function getPhone();

    /**
     * @return string
     */
    public function getContentDescription();

    public function getOrderId();

    public function getReference1();

    public function getReference2();
}