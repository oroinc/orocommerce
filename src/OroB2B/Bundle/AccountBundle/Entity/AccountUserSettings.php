<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\AccountBundle\Model\ExtendAccountUserSettings;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @Config()
 * @ORM\Entity
 * @ORM\Table(name="orob2b_account_user_settings")
 */
class AccountUserSettings extends ExtendAccountUserSettings
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var AccountUser
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUser", nullable=false)
     * @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $accountUser;

    /**
     * @var Website
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Website", nullable=false)
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $website;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3)
     */
    protected $currency;

    /**
     * @param Website $website
     */
    public function __construct(Website $website)
    {
        parent::__construct();
        $this->website = $website;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return AccountUser
     */
    public function getAccountUser()
    {
        return $this->accountUser;
    }

    /**
     * @param AccountUser $accountUser
     * @return $this
     */
    public function setAccountUser(AccountUser $accountUser)
    {
        $this->accountUser = $accountUser;

        return $this;
    }

    /**
     * @return Website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }
}
