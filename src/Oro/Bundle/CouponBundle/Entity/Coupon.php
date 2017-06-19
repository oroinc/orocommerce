<?php

namespace Oro\Bundle\CouponBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\BusinessUnitAwareTrait;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;

/**
 * @ORM\Table(
 *      name="oro_coupon",
 *      indexes={
 *          @ORM\Index(name="idx_oro_coupon_created_at", columns={"created_at"}),
 *          @ORM\Index(name="idx_oro_coupon_updated_at", columns={"updated_at"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\CouponBundle\Entity\Repository\CouponRepository")
 * @Config(
 *      routeName="oro_coupon_index",
 *      routeView="oro_coupon_view",
 *      routeUpdate="oro_coupon_update",
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
 *              "group_name"="commerce"
 *          }
 *      }
 * )
 */
class Coupon implements
    DatesAwareInterface
{
    use BusinessUnitAwareTrait;
    use DatesAwareTrait;

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
     *              "order"=10
     *          }
     *      }
     *  )
     */
    protected $code;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_uses", type="integer", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     *  )
     */
    protected $totalUses = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="uses_per_coupon", type="integer", nullable=true)
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
    protected $usesPerCoupon = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="uses_per_user", type="integer", nullable=true)
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
    protected $usesPerUser = 1;

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
     * @return int
     */
    public function getTotalUses()
    {
        return $this->totalUses;
    }

    /**
     * @param int $totalUses
     * @return Coupon
     */
    public function setTotalUses($totalUses)
    {
        $this->totalUses = $totalUses;

        return $this;
    }

    /**
     * @return int
     */
    public function getUsesPerCoupon()
    {
        return $this->usesPerCoupon;
    }

    /**
     * @param int $usesPerCoupon
     * @return Coupon
     */
    public function setUsesPerCoupon($usesPerCoupon)
    {
        $this->usesPerCoupon = $usesPerCoupon;

        return $this;
    }

    /**
     * @return int
     */
    public function getUsesPerUser()
    {
        return $this->usesPerUser;
    }

    /**
     * @param int $usesPerUser
     * @return Coupon
     */
    public function setUsesPerUser($usesPerUser)
    {
        $this->usesPerUser = $usesPerUser;

        return $this;
    }
}
