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
     * @var float
     *
     * @ORM\Column(name="handling_fee_value", type="money", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=50
     *          }
     *      }
     * )
     */
    protected $handlingFeeValue;

    /**
     * @var Price
     */
    protected $price;

    /**
     * @var Price
     */
    protected $handlingFee;

    /**
     * @ORM\PostLoad
     */
    public function createPrices()
    {
        if (null !== $this->value && null !== $this->currency) {
            $this->price = Price::create($this->value, $this->currency);
        }
        if (null !== $this->handlingFeeValue && null !== $this->currency) {
            $this->handlingFee = Price::create($this->handlingFeeValue, $this->currency);
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatePrices()
    {
        $this->value = $this->price ? $this->price->getValue() : null;
        $this->currency = $this->price ? $this->price->getCurrency() : null;
        $this->handlingFeeValue = $this->handlingFee ? $this->handlingFee->getValue() : null;
    }

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        $this->createPrices();

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
        $this->createPrices();

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
        $this->updatePrices();

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

    /**
     * @return float
     */
    public function getHandlingFeeValue()
    {
        return $this->handlingFeeValue;
    }

    /**
     * @param float $handlingFeeValue
     * @return $this
     */
    public function setHandlingFeeValue($handlingFeeValue)
    {
        $this->handlingFeeValue = $handlingFeeValue;
        $this->createPrices();

        return $this;
    }

    /**
     * @return Price
     */
    public function getHandlingFee()
    {
        return $this->handlingFee;
    }

    /**
     * @param Price $handlingFee
     * @return $this
     */
    public function setHandlingFee(Price $handlingFee)
    {
        $this->handlingFee = $handlingFee;
        $this->updatePrices();

        return $this;
    }
}
