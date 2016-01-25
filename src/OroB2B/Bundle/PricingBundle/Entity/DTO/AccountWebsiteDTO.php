<?php

namespace OroB2B\Bundle\PricingBundle\Entity\DTO;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountWebsiteDTO
{
    /** @var  Account */
    protected $account;

    /** @var  Website */
    protected $website;

    /**
     * @param Account $account
     * @param $website
     */
    public function __construct(Account $account, $website)
    {
        $this->account = $account;
        $this->website = $website;
    }

    /**
     * @return Account
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
