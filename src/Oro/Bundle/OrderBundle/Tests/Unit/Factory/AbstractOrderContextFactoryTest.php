<?php

namespace Oro\Bundle\OrderBundle\Bundle\Tests\Unit\Factory;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

abstract class AbstractOrderContextFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $builder
     * @param OrderAddress|\PHPUnit_Framework_MockObject_MockObject $address
     * @param Customer|\PHPUnit_Framework_MockObject_MockObject $customer
     * @param CustomerUser|\PHPUnit_Framework_MockObject_MockObject $customerUser
     */
    protected function prepareContextBuilder(
        \PHPUnit_Framework_MockObject_MockObject $builder,
        $address,
        $customer,
        $customerUser
    ) {
        $builder->method('setShippingAddress')->with($address);
        $builder->method('setBillingAddress')->with($address);
        $builder->method('setCustomer')->with($customer);
        $builder->method('setCustomerUser')->with($customerUser);
        $builder->expects($this->once())->method('getResult');
    }
}
