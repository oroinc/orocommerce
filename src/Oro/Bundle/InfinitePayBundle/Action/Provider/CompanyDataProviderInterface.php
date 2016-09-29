<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CompanyData;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

interface CompanyDataProviderInterface
{
    /**
     * @param OrderAddress $billingAddress
     * @param Customer $customer
     * @return CompanyData
     */
    public function getCompanyData(OrderAddress $billingAddress, Customer $customer);
}
