<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

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
     *      cascade={"persist"}
     * )
     */
    protected $currencies;
    
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
     * @param PriceListCurrency $currency
     *
     * @return PriceList
     */
    public function addCurrency(PriceListCurrency $currency)
    {
        if (!$this->currencies->contains($currency)) {
            $this->currencies->add($currency);
        }

        return $this;
    }

    /**
     * @param PriceListCurrency $currency
     *
     * @return PriceList
     */
    public function removeCurrency(PriceListCurrency $currency)
    {
        if ($this->currencies->contains($currency)) {
            $this->currencies->removeElement($currency);
        }

        return $this;
    }

    /**
     * Get currencies
     *
     * @return Collection|PriceListCurrency[]
     */
    public function getCurrencies()
    {
        return $this->currencies;
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
     * @return Pricelist
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
     * @return Pricelist
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
