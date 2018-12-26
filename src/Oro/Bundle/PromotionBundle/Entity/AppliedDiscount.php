<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait; // required by DatesAwareTrait
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

/**
 * Represents a discount applied to order line item.
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
     * @var AppliedPromotion
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PromotionBundle\Entity\AppliedPromotion", inversedBy="appliedDiscounts")
     * @ORM\JoinColumn(name="applied_promotion_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $appliedPromotion;

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
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrderBundle\Entity\OrderLineItem")
     * @ORM\JoinColumn(name="line_item_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
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
     * @return OrderLineItem
     */
    public function getLineItem()
    {
        return $this->lineItem;
    }

    /**
     * @param OrderLineItem|null $lineItem
     * @return $this
     */
    public function setLineItem($lineItem)
    {
        $this->lineItem = $lineItem;

        return $this;
    }

    /**
     * @return AppliedPromotion
     */
    public function getAppliedPromotion()
    {
        return $this->appliedPromotion;
    }

    /**
     * @param AppliedPromotion $appliedPromotion
     * @return $this
     */
    public function setAppliedPromotion(AppliedPromotion $appliedPromotion)
    {
        $this->appliedPromotion = $appliedPromotion;

        return $this;
    }
}
