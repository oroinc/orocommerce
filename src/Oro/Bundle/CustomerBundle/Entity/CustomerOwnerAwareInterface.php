<?php

namespace Oro\Bundle\CustomerBundle\Entity;

interface CustomerOwnerAwareInterface
{
    /**
     * @return \Oro\Bundle\CustomerBundle\Entity\Customer
     */
    public function getCustomer();

    /**
     * @return \Oro\Bundle\CustomerBundle\Entity\CustomerUser
     */
    public function getCustomerUser();
}
