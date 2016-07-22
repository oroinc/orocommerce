<?php

namespace OroB2B\Bundle\ShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\CurrencyBundle\Entity\Price;

/**
 * @ORM\Table(name="orob2b_ship_flat_rate_rule_cnf")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @Config
 */
class FlatRateRuleConfiguration extends ShippingRuleConfiguration
{
    const TYPE_PER_ORDER = 'per_order';
    const TYPE_PER_ITEM = 'per_item';

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="money", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=30
     *          }
     *      }
     * )
     */
    protected $value;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=40
     *          }
     *      }
     * )
     */
    protected $currency;

    /**
     * @var Price
     */
    protected $price;

    /**
     * @ORM\PostLoad
     */
    public function createPrice()
    {
        if (null !== $this->value && null !== $this->currency) {
            $this->price = Price::create($this->value, $this->currency);
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatePrice()
    {
        $this->value = $this->price ? $this->price->getValue() : null;
        $this->currency = $this->price ? $this->price->getCurrency() : null;
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
        $this->createPrice();

        return $this;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        $this->createPrice();

        return $this;
    }

    /**
     * Set price
     *
     * @param Price $price
     * @return $this
     */
    public function setPrice(Price $price = null)
    {
        $this->price = $price;

        $this->updatePrice();

        return $this;
    }

    /**
     * Get price
     *
     * @return Price|null
     */
    public function getPrice()
    {
        return $this->price;
    }
}
