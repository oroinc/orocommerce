<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Tests\Unit\Entity;

use OroB2B\Bundle\CustomerBundle\Tests\Unit\Entity\CustomerGroupTest as FrontendCustomerGroupTest;

use OroB2B\Bundle\CustomerAdminBundle\Entity\CustomerGroup;
use OroB2B\Bundle\CustomerAdminBundle\Entity\Customer;

class CustomerGroupTest extends FrontendCustomerGroupTest
{
    /**
     * @return CustomerGroup
     */
    protected function createCustomerGroupEntity()
    {
        return new CustomerGroup();
    }

    /**
     * @return Customer
     */
    protected function createCustomerEntity()
    {
        return new Customer();
    }
}
