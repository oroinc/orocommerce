<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\PromotionBundle\Model\ExtendPromotion;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;

/**
 * @ORM\Table(name="oro_promotion")
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
 *          }
 *      }
 * )
 */
class Promotion extends ExtendPromotion implements
    DatesAwareInterface,
    OrganizationAwareInterface
{
    use DatesAwareTrait;
    use UserAwareTrait;

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
     *              "excluded"=true
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
     *          @ORM\JoinColumn(name="node_id", referencedColumnName="id", onDelete="CASCADE")
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
     *          @ORM\JoinColumn(name="node_id", referencedColumnName="id", onDelete="CASCADE")
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
     *          @ORM\JoinColumn(name="node_id", referencedColumnName="id", onDelete="CASCADE")
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
     *      mappedBy="priceList",
     *      cascade={"persist"},
     *      orphanRemoval=true
     * )
     * @ORM\OrderBy({"activeAt" = "ASC"})
     */
    protected $schedules;

    /**
     * @var DiscountConfiguration
     *
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration",
     *     cascade={"persist", "remove"}
     * )
     * @ORM\JoinColumn(name="discount_config_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $discountConfiguration;

    /**
     * @var bool
     *
     * @ORM\Column(type="bool", name="use_coupons")
     */
    protected $useCoupons = false;

    /**
     * @var Collection|Coupon[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\PromotionBundle\Entity\Coupon"
     * )
     * @ORM\JoinTable(name="oro_promotion_to_coupon",
     *      joinColumns={
     *          @ORM\JoinColumn(name="promotion_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="coupon_id", referencedColumnName="id", onDelete="CASCADE")
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
}
