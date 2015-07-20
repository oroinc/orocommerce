<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Entity;

use OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerAddressToAddressType;

class CustomerAddressTest extends AbstractAddressTest
{
    protected function createAddressEntity()
    {
        return new CustomerAddress();
    }

    protected function createAddressToTypeEntity()
    {
        return new CustomerAddressToAddressType();
    }
}
