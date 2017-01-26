<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;

interface AddressProviderInterface
{
    /**
     * @param Customer $customer
     * @param string $type
     *
     * @return CustomerAddress[]
     * @throws \InvalidArgumentException
     */
    public function getCustomerAddresses(Customer $customer, $type);

    /**
     * @param CustomerUser $customerUser
     * @param string $type
     *
     * @return CustomerUserAddress[]
     * @throws \InvalidArgumentException
     */
    public function getCustomerUserAddresses(CustomerUser $customerUser, $type);

    /**
     * @param string $type
     * @throws \InvalidArgumentException
     */
    public static function assertType($type);
}
