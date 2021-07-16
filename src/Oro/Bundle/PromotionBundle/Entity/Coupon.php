<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\BusinessUnitAwareTrait;

/**
 * Coupon ORM entity
 *
 * @ORM\Table(
 *      name="oro_promotion_coupon",
 *      indexes={
 *          @ORM\Index(name="idx_oro_promotion_coupon_created_at", columns={"created_at"}),
 *          @ORM\Index(name="idx_oro_promotion_coupon_updated_at", columns={"updated_at"}),
 *          @ORM\Index(name="idx_oro_promotion_coupon_code_upper", columns={"code_uppercase"}),
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      routeName="oro_promotion_coupon_index",
 *      routeView="oro_promotion_coupon_view",
 *      routeCreate="oro_promotion_coupon_create",
 *      routeUpdate="oro_promotion_coupon_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-briefcase"
 *          },
 *          "ownership"={
 *              "owner_type"="BUSINESS_UNIT",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="business_unit_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "permissions"="VIEW;CREATE;EDIT;DELETE",
 *              "group_name"="commerce",
 *              "category"="marketing"
 *          }
 *      }
 * )
 */
class Coupon implements
    DatesAwareInterface,
    OrganizationAwareInterface
{
    use BusinessUnitAwareTrait;
    use DatesAwareTrait;

    const MAX_COUPON_CODE_LENGTH = 255;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
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
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=false, unique=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "identity"=true,
     *              "order"=10
     *          }
     *      }
     *  )
     */
    protected $code;

    /**
     * @var string
     * @ORM\Column(name="code_uppercase", type="string", length=255, nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     *  )
     */
    protected $codeUppercase;

    /**
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", nullable=false, options={"default"=false})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=20
     *          }
     *      }
     *  )
     */
    protected $enabled = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="uses_per_coupon", type="integer", nullable=true, options={"default"=1})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=30
     *          }
     *      }
     *  )
     */
    protected $usesPerCoupon = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="uses_per_person", type="integer", nullable=true, options={"default"=1})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=40
     *          }
     *      }
     *  )
     */
    protected $usesPerPerson = 1;

    /**
     * @var Promotion
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PromotionBundle\Entity\Promotion", inversedBy="coupons")
     * @ORM\JoinColumn(name="promotion_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=60
     *          }
     *      }
     *  )
     */
    protected $promotion;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    /**
     * @var OrganizationInterface
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $organization;

    /**
     * @ORM\Column(name="valid_from", type="datetime", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=45
     *          }
     *      }
     *  )
     *
     * @var \DateTime|null
     */
    protected $validFrom;

    /**
     * @ORM\Column(name="valid_until", type="datetime", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=50
     *          }
     *      }
     *  )
     *
     * @var \DateTime|null
     */
    protected $validUntil;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return Coupon
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getCodeUppercase()
    {
        return $this->codeUppercase;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return Coupon
     */
    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getUsesPerCoupon()
    {
        return $this->usesPerCoupon;
    }

    /**
     * @param int|null $usesPerCoupon
     * @return Coupon
     */
    public function setUsesPerCoupon($usesPerCoupon)
    {
        $this->usesPerCoupon = $usesPerCoupon;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getUsesPerPerson()
    {
        return $this->usesPerPerson;
    }

    /**
     * @param int|null $usesPerPerson
     * @return Coupon
     */
    public function setUsesPerPerson($usesPerPerson)
    {
        $this->usesPerPerson = $usesPerPerson;

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
     * @return Coupon
     */
    public function setPromotion($promotion)
    {
        $this->promotion = $promotion;

        return $this;
    }

    public function getValidFrom(): ?\DateTime
    {
        return $this->validFrom;
    }

    /**
     * @param \DateTime|null $validFrom
     * @return Coupon
     */
    public function setValidFrom(\DateTime $validFrom = null)
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    public function getValidUntil(): ?\DateTime
    {
        return $this->validUntil;
    }

    /**
     * @param \DateTime|null $validUntil
     * @return Coupon
     */
    public function setValidUntil(\DateTime $validUntil = null)
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    /**
     * Pre persist event handler.
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->updateCodeUppercase();
    }

    /**
     * Pre update event handler.
     *
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updateCodeUppercase();
    }

    private function updateCodeUppercase()
    {
        $this->codeUppercase = strtoupper($this->code);
    }
}
