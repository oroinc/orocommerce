<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Table(name="oro_price_list_ch_trigger")
 * @ORM\Entity(
 * repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\PriceListChangeTriggerRepository")
 */
class PriceListChangeTrigger
{
    /**
     * @var integer $id
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AccountBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $account;

    /** @var Website
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $website;

    /**
     * @var AccountGroup
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AccountBundle\Entity\AccountGroup")
     * @ORM\JoinColumn(name="account_group_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $accountGroup;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_force", type="boolean", nullable=true)
     */
    protected $force = false;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

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
}
