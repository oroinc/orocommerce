<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orob2b_price_list")
 * @ORM\Entity()
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
     */
    protected $name;

    /**
     * @var PriceListCurrency[]|Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceListCurrency",
     *      mappedBy="priceList",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     */
    protected $currencies;

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
     * @param PriceListCurrency $currency
     *
     * @return PriceList
     */
    public function addCurrency(PriceListCurrency $currency)
    {
        if (!$this->currencies->contains($currency)) {
            $currency->setPriceList($this);
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
}
