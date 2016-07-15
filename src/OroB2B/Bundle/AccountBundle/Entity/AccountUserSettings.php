<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\LocaleBundle\Entity\Localization;

use OroB2B\Bundle\AccountBundle\Model\ExtendAccountUserSettings;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @Config()
 * @ORM\Entity
 * @ORM\Table(
 *    name="orob2b_account_user_settings",
 *    uniqueConstraints={
 *        @ORM\UniqueConstraint(name="unique_acc_user_website", columns={"account_user_id", "website_id"})
 *    }
 * )
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
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUser", inversedBy="settings")
     * @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $accountUser;

    /**
     * @var Website
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $website;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    protected $currency;

    /**
     * @var Localization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\LocaleBundle\Entity\Localization")
     * @ORM\JoinColumn(name="localization_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $localization;

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

    /**
     * @return Localization
     */
    public function getLocalization()
    {
        return $this->localization;
    }

    /**
     * @param Localization $localization
     * @return $this
     */
    public function setLocalization(Localization $localization = null)
    {
        $this->localization = $localization;

        return $this;
    }
}
