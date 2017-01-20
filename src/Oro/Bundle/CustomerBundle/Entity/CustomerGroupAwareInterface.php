<?php

namespace Oro\Bundle\CustomerBundle\Entity;

interface CustomerGroupAwareInterface
{
    /**
     * @return CustomerGroup
     */
    public function getCustomerGroup();

    /**
     *
     * @param CustomerGroup $customerGroup
     * @return $this
     */
    public function setCustomerGroup(CustomerGroup $customerGroup);
}
