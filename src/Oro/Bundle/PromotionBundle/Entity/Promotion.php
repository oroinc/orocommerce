<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroPromotionBundle_Entity_Promotion;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Repository\PromotionRepository;
use Oro\Bundle\PromotionBundle\Form\Type\PromotionSelectType;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;

/**
 * Store promotion data in database.
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @method LocalizedFallbackValue getLabel(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultLabel()
 * @method LocalizedFallbackValue getDescription(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultDescription()
 * @method setDefaultLabel($title)
 * @method setDefaultDescription($slug)
 * @mixin OroPromotionBundle_Entity_Promotion
 */
#[ORM\Entity(repositoryClass: PromotionRepository::class)]
#[ORM\Table(name: 'oro_promotion')]
#[Config(
    routeName: 'oro_promotion_index',
    routeView: 'oro_promotion_view',
    routeCreate: 'oro_promotion_create',
    routeUpdate: 'oro_promotion_update',
    defaultValues: [
        'ownership' => [
            'owner_type' => 'USER',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'user_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'dataaudit' => ['auditable' => true],
        'security' => ['type' => 'ACL', 'group_name' => ''],
        'form' => ['form_type' => PromotionSelectType::class, 'grid_name' => 'promotion-select-grid']
    ]
)]
class Promotion implements
    DatesAwareInterface,
    OrganizationAwareInterface,
    PromotionDataInterface,
    ExtendEntityInterface
{
    use DatesAwareTrait;
    use UserAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Rule::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'rule_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?Rule $rule = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_promotion_label')]
    #[ORM\JoinColumn(name: 'promotion_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Collection $labels = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_promotion_description')]
    #[ORM\JoinColumn(name: 'promotion_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Collection $descriptions = null;

    /**
     * @var Collection<int, Scope>
     */
    #[ORM\ManyToMany(targetEntity: Scope::class)]
    #[ORM\JoinTable(name: 'oro_promotion_scope')]
    #[ORM\JoinColumn(name: 'promotion_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'scope_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $scopes = null;

    /**
     * @var Collection<int, PromotionSchedule>
     */
    #[ORM\OneToMany(
        mappedBy: 'promotion',
        targetEntity: PromotionSchedule::class,
        cascade: ['persist'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['activeAt' => Criteria::ASC])]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Collection $schedules = null;

    #[ORM\OneToOne(targetEntity: DiscountConfiguration::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'discount_config_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?DiscountConfiguration $discountConfiguration = null;

    #[ORM\Column(name: 'use_coupons', type: Types::BOOLEAN)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?bool $useCoupons = false;

    /**
     * @var Collection<int, Coupon>
     */
    #[ORM\OneToMany(mappedBy: 'promotion', targetEntity: Coupon::class, fetch: 'EXTRA_LAZY')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Collection $coupons = null;

    #[ORM\ManyToOne(targetEntity: Segment::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'products_segment_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Segment $productsSegment = null;

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
