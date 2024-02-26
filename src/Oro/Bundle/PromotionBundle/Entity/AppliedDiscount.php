<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroPromotionBundle_Entity_AppliedDiscount;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

/**
 * Represents a discount applied to order line item.
 *
 * @mixin OroPromotionBundle_Entity_AppliedDiscount
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_promotion_applied_discount')]
#[Config]
class AppliedDiscount implements
    DatesAwareInterface,
    ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AppliedPromotion::class, inversedBy: 'appliedDiscounts')]
    #[ORM\JoinColumn(name: 'applied_promotion_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?AppliedPromotion $appliedPromotion = null;

    /**
     * @var float
     */
    #[ORM\Column(name: 'amount', type: 'money_value')]
    protected $amount;

    /**
     * @var string
     */
    #[ORM\Column(name: 'currency', type: 'currency', length: 3)]
    protected $currency;

    #[ORM\ManyToOne(targetEntity: OrderLineItem::class)]
    #[ORM\JoinColumn(name: 'line_item_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?OrderLineItem $lineItem = null;

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
