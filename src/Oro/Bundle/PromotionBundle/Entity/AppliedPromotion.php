<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroPromotionBundle_Entity_AppliedPromotion;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedPromotionRepository;

/**
 * Represents applied promotions to the order
 *
 *
 * @method Order getOrder()
 * @method setOrder(Order $order)
 * @mixin OroPromotionBundle_Entity_AppliedPromotion
 */
#[ORM\Entity(repositoryClass: AppliedPromotionRepository::class)]
#[ORM\Table(name: 'oro_promotion_applied')]
#[Config]
class AppliedPromotion implements DatesAwareInterface, ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'active', type: Types::BOOLEAN, options: ['default' => true])]
    protected ?bool $active = true;

    #[ORM\Column(name: 'removed', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $removed = false;

    #[ORM\OneToOne(mappedBy: 'appliedPromotion', targetEntity: AppliedCoupon::class, cascade: ['all'])]
    protected ?AppliedCoupon $appliedCoupon = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 255)]
    protected ?string $type = null;

    #[ORM\Column(name: 'source_promotion_id', type: Types::INTEGER)]
    protected ?int $sourcePromotionId = null;

    #[ORM\Column(name: 'promotion_name', type: Types::TEXT)]
    protected ?string $promotionName = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'config_options', type: 'json')]
    protected $configOptions = [];

    /**
     * @var array
     */
    #[ORM\Column(name: 'promotion_data', type: 'json')]
    protected $promotionData = [];

    /**
     * @var Collection<int, AppliedDiscount>
     */
    #[ORM\OneToMany(
        mappedBy: 'appliedPromotion',
        targetEntity: AppliedDiscount::class,
        cascade: ['persist'],
        orphanRemoval: true
    )]
    protected ?Collection $appliedDiscounts = null;

    public function __construct()
    {
        $this->appliedDiscounts = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function isActive(): bool
    {
        return (bool)$this->active;
    }

    /**
     * @param bool $active
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = (bool)$active;

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
     * @return $this
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param AppliedCoupon|null $appliedCoupon
     * @return $this
     */
    public function setAppliedCoupon(?AppliedCoupon $appliedCoupon = null)
    {
        if ($appliedCoupon) {
            $appliedCoupon->setAppliedPromotion($this);
        }

        $this->appliedCoupon = $appliedCoupon;

        return $this;
    }

    /**
     * @return AppliedCoupon|null
     */
    public function getAppliedCoupon()
    {
        return $this->appliedCoupon;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setSourcePromotionId($id)
    {
        $this->sourcePromotionId = (int)$id;

        return $this;
    }

    /**
     * @return int
     */
    public function getSourcePromotionId()
    {
        return $this->sourcePromotionId;
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
     * @return $this
     */
    public function setPromotionName(string $promotionName)
    {
        $this->promotionName = $promotionName;

        return $this;
    }

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

    public function getPromotionData(): array
    {
        return $this->promotionData;
    }

    /**
     * @param array $promotionData
     * @return $this
     */
    public function setPromotionData(array $promotionData)
    {
        $this->promotionData = $promotionData;

        return $this;
    }

    /**
     * @return Collection|AppliedDiscount[]
     */
    public function getAppliedDiscounts()
    {
        return $this->appliedDiscounts;
    }

    /**
     * @param AppliedDiscount $appliedDiscount
     * @return $this
     */
    public function addAppliedDiscount(AppliedDiscount $appliedDiscount)
    {
        if (!$this->appliedDiscounts->contains($appliedDiscount)) {
            $appliedDiscount->setAppliedPromotion($this);
            $this->appliedDiscounts->add($appliedDiscount);
        }

        return $this;
    }

    /**
     * @param AppliedDiscount $appliedDiscount
     * @return $this
     */
    public function removeAppliedDiscount(AppliedDiscount $appliedDiscount)
    {
        if ($this->appliedDiscounts->contains($appliedDiscount)) {
            $this->appliedDiscounts->removeElement($appliedDiscount);
        }

        return $this;
    }

    public function isRemoved(): bool
    {
        return (bool)$this->removed;
    }

    public function setRemoved(?bool $removed): self
    {
        $this->removed = (bool)$removed;

        return $this;
    }
}
