<?php

namespace OroB2B\Bundle\OrderBundle\Model;

use Oro\Bundle\AddressBundle\Entity\AbstractTypedAddress;

abstract class ExtendOrderAddress extends AbstractTypedAddress
{
    /**
     * Constructor
     *
     * The real implementation of this method is auto generated.
     *
     * IMPORTANT: If the derived class has own constructor it must call parent constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }
}
