<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Represents applied promotions to the order
 *
 * @Config()
 * @ORM\Table(name="oro_promotion_applied")
 * @ORM\Entity(repositoryClass="Oro\Bundle\PromotionBundle\Entity\Repository\AppliedPromotionRepository")
 *
 * @method Order getOrder()
 * @method setOrder(Order $order)
 */
class AppliedPromotion implements DatesAwareInterface, ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var integer
     */
    protected $id;

    /**
     * @ORM\Column(type="boolean", name="active", options={"default"=true})
     *
     * @var bool
     */
    protected $active = true;

    /**
     * @var AppliedCoupon|null
     *
     * @ORM\OneToOne(
     *     targetEntity="Oro\Bundle\PromotionBundle\Entity\AppliedCoupon",
     *     mappedBy="appliedPromotion",
     *     cascade={"all"}
     * )
     */
    protected $appliedCoupon;

    /**
     * @ORM\Column(name="type", type="string", length=255)
     *
     * @var string
     */
    protected $type;

    /**
     * @ORM\Column(name="source_promotion_id", type="integer"))
     * @var int
     */
    protected $sourcePromotionId;

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
     * @ORM\Column(name="promotion_data", type="json_array")
     *
     * @var array
     */
    protected $promotionData = [];

    /**
     * @var Collection|AppliedDiscount[]
     *
     * @ORM\OneToMany(
     *      targetEntity="Oro\Bundle\PromotionBundle\Entity\AppliedDiscount",
     *      mappedBy="appliedPromotion",
     *      cascade={"persist"},
     *      orphanRemoval=true
     * )
     */
    protected $appliedDiscounts;

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
    public function setAppliedCoupon(AppliedCoupon $appliedCoupon = null)
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
}
