<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Table(name="oro_promotion_applied_discount")
 * @ORM\Entity()
 */
class AppliedDiscount implements DatesAwareInterface
{
    use DatesAwareTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var integer
     */
    protected $id;

    /**
     * @ORM\Column(name="class", type="text")
     *
     * @var string
     */
    protected $class;

    /**
     * @ORM\Column(name="amount", type="money_value")
     *
     * @var float
     */
    protected $amount;

    /**
     * @ORM\Column(name="currency", type="currency", length=3)
     *
     * @var string
     */
    protected $currency;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrderBundle\Entity\Order")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE")
     *
     * @var Order
     */
    protected $order;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PromotionBundle\Entity\Promotion")
     * @ORM\JoinColumn(name="promotion_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     *
     * @var Promotion|null
     */
    protected $promotion;

    /**
     * @ORM\Column(name="promotion_name", type="text")
     *
     * @var string
     */
    protected $promotionName;

    /**
     * @ORM\Column(name="config_options", type="json_array")
     *
     * @var array
     */
    protected $configOptions = [];

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrderBundle\Entity\OrderLineItem")
     * @ORM\JoinColumn(name="line_item_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     *
     * @var OrderLineItem|null
     */
    protected $lineItem;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     * @return AppliedDiscount
     */
    public function setClass(string $class): AppliedDiscount
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     * @return $this
     */
    public function setAmount(float $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * @param Order $order
     * @return $this
     */
    public function setOrder(Order $order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return Promotion|null
     */
    public function getPromotion()
    {
        return $this->promotion;
    }

    /**
     * @param Promotion $promotion
     * @return $this
     */
    public function setPromotion(Promotion $promotion)
    {
        $this->promotion = $promotion;

        return $this;
    }

    /**
     * @return string
     */
    public function getPromotionName(): string
    {
        return $this->promotionName;
    }

    /**
     * @param string $promotionName
     * @return AppliedDiscount
     */
    public function setPromotionName(string $promotionName): AppliedDiscount
    {
        $this->promotionName = $promotionName;

        return $this;
    }

    /**
     * @return array
     */
    public function getConfigOptions(): array
    {
        return $this->configOptions;
    }

    /**
     * @param array $configOptions
     * @return $this
     */
    public function setConfigOptions(array $configOptions)
    {
        $this->configOptions = $configOptions;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency(string $currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return OrderLineItem|null
     */
    public function getLineItem()
    {
        return $this->lineItem;
    }

    /**
     * @param OrderLineItem $lineItem
     * @return AppliedDiscount
     */
    public function setLineItem(OrderLineItem $lineItem): AppliedDiscount
    {
        $this->lineItem = $lineItem;

        return $this;
    }
}
