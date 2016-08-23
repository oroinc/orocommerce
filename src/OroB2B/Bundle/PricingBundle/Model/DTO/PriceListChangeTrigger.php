<?php

namespace Oro\Bundle\PricingBundle\Model\DTO;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class PriceListChangeTrigger
{
    const WEBSITE = 'website';
    const ACCOUNT = 'account';
    const ACCOUNT_GROUP = 'accountGroup';
    const FORCE = 'force';

    /**
     * @var Account
     */
    protected $account;

    /**
     * @var Website
     */
    protected $website;

    /**
     * @var AccountGroup
     */
    protected $accountGroup;

    /**
     * @var bool
     */
    protected $force = false;

    /**
     * @return Account|null
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Account|null $account
     * @return $this
     */
    public function setAccount(Account $account = null)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * @return Website|null
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param Website|null $website
     * @return $this
     */
    public function setWebsite(Website $website = null)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return AccountGroup|null
     */
    public function getAccountGroup()
    {
        return $this->accountGroup;
    }

    /**
     * @param AccountGroup|null $accountGroup
     * @return $this
     */
    public function setAccountGroup(AccountGroup $accountGroup = null)
    {
        $this->accountGroup = $accountGroup;

        return $this;
    }

    /**
     * @return bool
     */
    public function isForce()
    {
        return $this->force;
    }

    /**
     * @param bool $force
     * @return $this
     */
    public function setForce($force)
    {
        $this->force = (bool)$force;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            self::WEBSITE => null !== $this->website ? $this->website->getId() : null,
            self::ACCOUNT => null !== $this->account ? $this->account->getId() : null,
            self::ACCOUNT_GROUP => null !== $this->accountGroup ? $this->accountGroup->getId() : null,
            self::FORCE => $this->force,
        ];
    }
}
