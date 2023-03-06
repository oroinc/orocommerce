<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;

/**
 * Store promotion data in database.
 *
 * @ORM\Table(name="oro_promotion")
 * @ORM\Entity(repositoryClass="Oro\Bundle\PromotionBundle\Entity\Repository\PromotionRepository")
 * @Config(
 *      routeName="oro_promotion_index",
 *      routeView="oro_promotion_view",
 *      routeCreate="oro_promotion_create",
 *      routeUpdate="oro_promotion_update",
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "form"={
 *              "form_type"="Oro\Bundle\PromotionBundle\Form\Type\PromotionSelectType",
 *              "grid_name"="promotion-select-grid"
 *          },
 *      }
 * )
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @method LocalizedFallbackValue getLabel(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultLabel()
 * @method LocalizedFallbackValue getDescription(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultDescription()
 * @method setDefaultLabel($title)
 * @method setDefaultDescription($slug)
 */
class Promotion implements
    DatesAwareInterface,
    OrganizationAwareInterface,
    PromotionDataInterface,
    ExtendEntityInterface
{
    use DatesAwareTrait;
    use UserAwareTrait;
    use ExtendEntityTrait;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $id;

    /**
     * @var RuleInterface
     *
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\RuleBundle\Entity\Rule",
     *     cascade={"persist", "remove"}
     * )
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $rule;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_promotion_label",
     *      joinColumns={
     *          @ORM\JoinColumn(name="promotion_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $labels;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_promotion_description",
     *      joinColumns={
     *          @ORM\JoinColumn(name="promotion_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $descriptions;

    /**
     * @var Collection|Scope[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\ScopeBundle\Entity\Scope"
     * )
     * @ORM\JoinTable(name="oro_promotion_scope",
     *      joinColumns={
     *          @ORM\JoinColumn(name="promotion_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="scope_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
     * )
     */
    protected $scopes;

    /**
     * @var Collection|PromotionSchedule[]
     *
     * @ORM\OneToMany(
     *      targetEntity="Oro\Bundle\PromotionBundle\Entity\PromotionSchedule",
     *      mappedBy="promotion",
     *      cascade={"persist"},
     *      orphanRemoval=true
     * )
     * @ORM\OrderBy({"activeAt" = "ASC"})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $schedules;

    /**
     * @var DiscountConfiguration
     *
     * @ORM\OneToOne(
     *     targetEntity="Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration",
     *     cascade={"persist", "remove"}
     * )
     * @ORM\JoinColumn(name="discount_config_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $discountConfiguration;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", name="use_coupons")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $useCoupons = false;

    /**
     * @var Collection|Coupon[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\PromotionBundle\Entity\Coupon",
     *     mappedBy="promotion",
     *     fetch="EXTRA_LAZY"
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $coupons;

    /**
     * @var Segment
     *
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\SegmentBundle\Entity\Segment",
     *     cascade={"persist", "remove"}
     * )
     * @ORM\JoinColumn(name="products_segment_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $productsSegment;

    public function __construct()
    {
        $this->labels = new ArrayCollection();
        $this->descriptions = new ArrayCollection();
        $this->scopes = new ArrayCollection();
        $this->schedules = new ArrayCollection();
        $this->coupons = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param RuleInterface $rule
     * @return $this
     */
    public function setRule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @param LocalizedFallbackValue $label
     * @return $this
     */
    public function addLabel(LocalizedFallbackValue $label)
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $label
     * @return $this
     */
    public function removeLabel(LocalizedFallbackValue $label)
    {
        if ($this->labels->contains($label)) {
            $this->labels->removeElement($label);
        }

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getDescriptions()
    {
        return $this->descriptions;
    }

    /**
     * @param LocalizedFallbackValue $description
     * @return $this
     */
    public function addDescription(LocalizedFallbackValue $description)
    {
        if (!$this->descriptions->contains($description)) {
            $this->descriptions->add($description);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $description
     * @return $this
     */
    public function removeDescription(LocalizedFallbackValue $description)
    {
        if ($this->descriptions->contains($description)) {
            $this->descriptions->removeElement($description);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * @return $this
     */
    public function resetScopes()
    {
        $this->scopes->clear();

        return $this;
    }

    /**
     * @param Scope $scope
     * @return $this
     */
    public function addScope(Scope $scope)
    {
        if (!$this->scopes->contains($scope)) {
            $this->scopes->add($scope);
        }

        return $this;
    }

    /**
     * @param Scope $scope
     * @return $this
     */
    public function removeScope(Scope $scope)
    {
        if ($this->scopes->contains($scope)) {
            $this->scopes->removeElement($scope);
        }

        return $this;
    }

    /**
     * @return Collection|PromotionSchedule[]
     */
    public function getSchedules()
    {
        return $this->schedules;
    }

    /**
     * @param PromotionSchedule $schedule
     * @return $this
     */
    public function addSchedule(PromotionSchedule $schedule)
    {
        if (!$this->schedules->contains($schedule)) {
            $schedule->setPromotion($this);
            $this->schedules->add($schedule);
        }

        return $this;
    }

    /**
     * @param PromotionSchedule $schedule
     * @return $this
     */
    public function removeSchedule(PromotionSchedule $schedule)
    {
        if ($this->schedules->contains($schedule)) {
            $this->schedules->removeElement($schedule);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountConfiguration()
    {
        return $this->discountConfiguration;
    }

    /**
     * @param DiscountConfiguration $discountConfiguration
     * @return $this
     */
    public function setDiscountConfiguration($discountConfiguration)
    {
        $this->discountConfiguration = $discountConfiguration;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isUseCoupons()
    {
        return $this->useCoupons;
    }

    /**
     * @param bool $useCoupons
     * @return $this
     */
    public function setUseCoupons($useCoupons)
    {
        $this->useCoupons = $useCoupons;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCoupons()
    {
        return $this->coupons;
    }

    /**
     * @param Coupon $coupon
     * @return $this
     */
    public function addCoupon(Coupon $coupon)
    {
        if (!$this->coupons->contains($coupon)) {
            $this->coupons->add($coupon);
        }

        return $this;
    }

    /**
     * @param Coupon $coupon
     * @return $this
     */
    public function removeCoupon(Coupon $coupon)
    {
        if ($this->coupons->contains($coupon)) {
            $this->coupons->removeElement($coupon);
        }

        return $this;
    }

    /**
     * @return Segment
     */
    public function getProductsSegment()
    {
        return $this->productsSegment;
    }

    /**
     * @param Segment $productsSegment
     * @return $this
     */
    public function setProductsSegment(Segment $productsSegment)
    {
        $this->productsSegment = $productsSegment;

        return $this;
    }
}
