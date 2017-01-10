<?php

namespace Oro\Bundle\PricingBundle\Model\DTO;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class AccountWebsiteDTO
{
    /**
     * @var  Customer
     */
    protected $account;

    /**
     * @var  Website
     */
    protected $website;

    /**
     * @param Customer $account
     * @param Website $website
     */
    public function __construct(Customer $account, Website $website)
    {
        $this->account = $account;
        $this->website = $website;
    }

    /**
     * @return Customer
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @return Website
     */
    public function getWebsite()
    {
        return $this->website;
    }
}
