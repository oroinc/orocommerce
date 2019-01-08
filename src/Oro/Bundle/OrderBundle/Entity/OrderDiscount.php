<?php

namespace Oro\Bundle\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * Represents discounts that are applied to an order.
 * @ORM\Table("oro_order_discount")
 * @Config(
 *       defaultValues={
 *          "entity"={
 *              "icon"="fa-discount"
 *          }
 *      }
 * )
 * @ORM\Entity
 */
class OrderDiscount
{
    const TYPE_AMOUNT = 'oro_order_discount_item_type_amount';
    const TYPE_PERCENT = 'oro_order_discount_item_type_percent';

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var float
     *
     * @ORM\Column(name="percent", type="percent", nullable=true)
     */
    protected $percent;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="money", nullable=false)
     */
    protected $amount;

    /**
     * @var Price
     */
    protected $amountPrice;

    /**
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrderBundle\Entity\Order", inversedBy="discounts")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $order;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     */
    protected $type;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return OrderDiscount
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set percent
     *
     * @param float $percent
     *
     * @return OrderDiscount
     */
    public function setPercent($percent)
    {
        $this->percent = $percent;

        return $this;
    }

    /**
     * Get percent
     *
     * @return float
     */
    public function getPercent()
    {
        return $this->percent;
    }

    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return OrderDiscount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set order
     *
     * @param Order $order
     *
     * @return OrderDiscount
     */
    public function setOrder(Order $order = null)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)($this->getAmount() . ' (' . $this->getOrder()->getCurrency() . ')');
    }

    /**
     * @return Price
     */
    public function getAmountPrice()
    {
        if ($this->amountPrice === null) {
            $this->amountPrice = Price::create((double)$this->getAmount(), $this->getOrder()->getCurrency());
        }
        return $this->amountPrice;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->getType() == self::TYPE_PERCENT ? $this->getPercent() : $this->getAmount();
    }
}
