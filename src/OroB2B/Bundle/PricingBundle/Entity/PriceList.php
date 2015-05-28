<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
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
     * @var Customer[]|Collection
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\CustomerBundle\Entity\Customer", mappedBy="priceList")
     */
    protected $customers;

    /**
     * @var CustomerGroup[]|Collection
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup", mappedBy="priceList")
     */
    protected $customerGroups;

    /**
     * @var Website[]|Collection
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Website", mappedBy="priceList")
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

    public function __construct()
    {
        $this->currencies = new ArrayCollection();
        $this->customers = new ArrayCollection();
        $this->customerGroups = new ArrayCollection();
        $this->websites = new ArrayCollection();
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
        return $this->currencies->map(
            function (PriceListCurrency $priceListCurrency) {
                return $priceListCurrency->getCurrency();
            }
        )->toArray();
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
     * @param Customer $customer
     *
     * @return PriceList
     */
    public function addCustomer(Customer $customer)
    {
        if (!$this->customers->contains($customer)) {
            $this->customers->add($customer);
        }

        return $this;
    }

    /**
     * @param Customer $customer
     *
     * @return PriceList
     */
    public function removeCustomer(Customer $customer)
    {
        if ($this->customers->contains($customer)) {
            $this->customers->removeElement($customer);
            $customer->setPriceList(null);
        }

        return $this;
    }

    /**
     * Get customers
     *
     * @return Collection|Customer[]
     */
    public function getCustomers()
    {
        return $this->customers;
    }

    /**
     * @param CustomerGroup $customerGroup
     *
     * @return PriceList
     */
    public function addCustomerGroup(CustomerGroup $customerGroup)
    {
        if (!$this->customerGroups->contains($customerGroup)) {
            $this->customerGroups->add($customerGroup);
            $customerGroup->setPriceList($this);
        }

        return $this;
    }

    /**
     * @param CustomerGroup $customerGroup
     *
     * @return PriceList
     */
    public function removeCustomerGroup(CustomerGroup $customerGroup)
    {
        if ($this->customerGroups->contains($customerGroup)) {
            $this->customerGroups->removeElement($customerGroup);
            $customerGroup->setPriceList(null);
        }

        return $this;
    }

    /**
     * Get customer groups
     *
     * @return Collection|CustomerGroup[]
     */
    public function getCustomerGroups()
    {
        return $this->customerGroups;
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
            $website->setPriceList($this);
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
            $website->setPriceList(null);
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
