<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Table(name="orob2b_price_list")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository")
 * @Config(
 *      routeName="orob2b_pricing_price_list_index",
 *      routeView="orob2b_pricing_price_list_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "form"={
 *              "form_type"="orob2b_pricing_price_list_select",
 *              "grid_name"="pricing-price-list-select-grid",
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class PriceList
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $name;

    /**
     * @var PriceListCurrency[]|Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceListCurrency",
     *      mappedBy="priceList",
     *      cascade={"all"},
     *      orphanRemoval=true
     * )
     */
    protected $currencies;

    /**
     * @var Account[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Account")
     * @ORM\JoinTable(
     *      name="orob2b_price_list_to_customer",
     *      joinColumns={
     *          @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="customer_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $accounts;

    /**
     * @var AccountGroup[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountGroup")
     * @ORM\JoinTable(
     *      name="orob2b_price_list_to_c_group",
     *      joinColumns={
     *          @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="customer_group_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $accountGroups;

    /**
     * @var Website[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinTable(
     *      name="orob2b_price_list_to_website",
     *      joinColumns={
     *          @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $websites;
    
    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime $updatedAt
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_default", type="boolean")
     */
    protected $default = false;

    /**
     * @var Collection|ProductPrice[]
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\PricingBundle\Entity\ProductPrice",
     *      mappedBy="priceList",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     **/
    protected $prices;

    public function __construct()
    {
        $this->currencies = new ArrayCollection();
        $this->accounts = new ArrayCollection();
        $this->accountGroups = new ArrayCollection();
        $this->websites = new ArrayCollection();
        $this->prices = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return PriceList
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param bool $default
     *
     * @return PriceList
     */
    public function setDefault($default)
    {
        $this->default = (bool)$default;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * @param array|string[] $currencies
     * @return PriceList
     */
    public function setCurrencies(array $currencies)
    {
        $knownCurrencies = $this->getCurrencies();
        $removedCurrencies = array_diff($knownCurrencies, $currencies);
        $addedCurrencies = array_diff($currencies, $knownCurrencies);

        foreach ($removedCurrencies as $currency) {
            $this->removeCurrencyByCode($currency);
        }

        foreach ($addedCurrencies as $currency) {
            $this->addCurrencyByCode($currency);
        }

        return $this;
    }

    /**
     * Get currencies
     *
     * @return array|string[]
     */
    public function getCurrencies()
    {
        $currencies = $this->currencies
            ->map(
                function (PriceListCurrency $priceListCurrency) {
                    return $priceListCurrency->getCurrency();
                }
            )
            ->toArray();

        sort($currencies);

        return $currencies;
    }

    /**
     * @param string $currency
     *
     * @return PriceList
     */
    public function addCurrencyByCode($currency)
    {
        if (!$this->hasCurrencyCode($currency)) {
            $priceListCurrency = new PriceListCurrency();
            $priceListCurrency->setPriceList($this);
            $priceListCurrency->setCurrency($currency);
            $this->currencies->add($priceListCurrency);
        }

        return $this;
    }

    /**
     * @param string $currency
     * @return bool
     */
    public function hasCurrencyCode($currency)
    {
        return (bool)$this->getPriceListCurrencyByCode($currency);
    }

    /**
     * @param string $currency
     *
     * @return PriceList
     */
    public function removeCurrencyByCode($currency)
    {
        $priceListCurrency = $this->getPriceListCurrencyByCode($currency);
        if ($priceListCurrency) {
            $this->currencies->removeElement($priceListCurrency);
        }

        return $this;
    }

    /**
     * @param string $currency
     * @return PriceListCurrency
     */
    public function getPriceListCurrencyByCode($currency)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('currency', $currency));

        return $this->currencies->matching($criteria)->first();
    }

    /**
     * @param Account $account
     *
     * @return PriceList
     */
    public function addAccount(Account $account)
    {
        if (!$this->accounts->contains($account)) {
            $this->accounts->add($account);
        }

        return $this;
    }

    /**
     * @param Account $account
     *
     * @return PriceList
     */
    public function removeAccount(Account $account)
    {
        if ($this->accounts->contains($account)) {
            $this->accounts->removeElement($account);
        }

        return $this;
    }

    /**
     * Get accounts
     *
     * @return Collection|Account[]
     */
    public function getAccounts()
    {
        return $this->accounts;
    }

    /**
     * @param AccountGroup $accountGroup
     *
     * @return PriceList
     */
    public function addAccountGroup(AccountGroup $accountGroup)
    {
        if (!$this->accountGroups->contains($accountGroup)) {
            $this->accountGroups->add($accountGroup);
        }

        return $this;
    }

    /**
     * @param AccountGroup $accountGroup
     *
     * @return PriceList
     */
    public function removeAccountGroup(AccountGroup $accountGroup)
    {
        if ($this->accountGroups->contains($accountGroup)) {
            $this->accountGroups->removeElement($accountGroup);
        }

        return $this;
    }

    /**
     * Get account groups
     *
     * @return Collection|AccountGroup[]
     */
    public function getAccountGroups()
    {
        return $this->accountGroups;
    }

    /**
     * @param Website $website
     *
     * @return PriceList
     */
    public function addWebsite(Website $website)
    {
        if (!$this->websites->contains($website)) {
            $this->websites->add($website);
        }

        return $this;
    }

    /**
     * @param Website $website
     *
     * @return PriceList
     */
    public function removeWebsite(Website $website)
    {
        if ($this->websites->contains($website)) {
            $this->websites->removeElement($website);
        }

        return $this;
    }

    /**
     * Get websites
     *
     * @return Collection|Website[]
     */
    public function getWebsites()
    {
        return $this->websites;
    }

    /**
     * @param ProductPrice $price
     * @return PriceList
     */
    public function addPrice(ProductPrice $price)
    {
        if (!$this->prices->contains($price)) {
            $price->setPriceList($this);
            $this->prices->add($price);
        }

        return $this;
    }

    /**
     * @param ProductPrice $price
     * @return PriceList
     */
    public function removePrice(ProductPrice $price)
    {
        if ($this->prices->contains($price)) {
            $this->prices->removeElement($price);
        }

        return $this;
    }

    /**
     * @return Collection|ProductPrice[]
     */
    public function getPrices()
    {
        return $this->prices;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return PriceList
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return PriceList
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Pre persist event handler
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        if (!$this->createdAt) {
            $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Pre update event handler
     *
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
