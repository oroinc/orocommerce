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
    const PROCESSING_TYPE_PER_ORDER = 'per_order';
    const PROCESSING_TYPE_PER_ITEM = 'per_item';

    /**
     * @var string
     *
     * @ORM\Column(name="processing_type", type="string", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=50
     *          }
     *      }
     * )
     */
    protected $processingType;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="money", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=60
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
     *              "order"=70
     *          }
     *      }
     * )
     */
    protected $handlingFeeValue;

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

        return $this;
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

        return $this;
    }

    /**
     * @return string
     */
    public function getProcessingType()
    {
        return $this->processingType;
    }

    /**
     * @param string $processingType
     * @return $this
     */
    public function setProcessingType($processingType)
    {
        $this->processingType = $processingType;
        return $this;
    }

    /**
     * Get price
     *
     * @return Price|null
     */
    public function getPrice()
    {
        return Price::create($this->value, $this->getCurrency());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf(
            '%s, %g',
            $this->getMethod(),
            $this->getValue()
        );
    }
}
