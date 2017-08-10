<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField; // required by DatesAwareTrait
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Model\ExtendAppliedDiscount;

/**
 * @Config()
 * @ORM\Table(name="oro_promotion_applied_discount")
 * @ORM\Entity(repositoryClass="Oro\Bundle\PromotionBundle\Entity\Repository\AppliedDiscountRepository")
 */
class AppliedDiscount extends ExtendAppliedDiscount implements DatesAwareInterface
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
     * @ORM\Column(type="boolean", name="enabled", options={"default"=true})
     *
     * @var bool
     */
    protected $enabled = true;

    /**
     * @ORM\Column(name="coupon_code", type="string", length=255, nullable=true)
     *
     * @var string|null
     */
    protected $couponCode;

    /**
     * @ORM\Column(name="type", type="string", length=255)
     *
     * @var string
     */
    protected $type;

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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->enabled;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCouponCode()
    {
        return $this->couponCode;
    }

    /**
     * @param string|null $couponCode
     * @return $this
     */
    public function setCouponCode($couponCode)
    {
        $this->couponCode = $couponCode;

        return $this;
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
     * @return AppliedDiscount
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
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
    public function getPromotionName()
    {
        return $this->promotionName;
    }

    /**
     * @param string $promotionName
     * @return AppliedDiscount
     */
    public function setPromotionName(string $promotionName)
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
    public function getCurrency()
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
    public function setLineItem(OrderLineItem $lineItem)
    {
        $this->lineItem = $lineItem;

        return $this;
    }
}
