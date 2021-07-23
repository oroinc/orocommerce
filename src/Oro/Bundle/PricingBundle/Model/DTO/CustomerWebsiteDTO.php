<?php

namespace Oro\Bundle\PricingBundle\Model\DTO;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CustomerWebsiteDTO
{
    /**
     * @var  Customer
     */
    protected $customer;

    /**
     * @var  Website
     */
    protected $website;

    public function __construct(Customer $customer, Website $website)
    {
        $this->customer = $customer;
        $this->website = $website;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @return Website
     */
    public function getWebsite()
    {
        return $this->website;
    }
}
