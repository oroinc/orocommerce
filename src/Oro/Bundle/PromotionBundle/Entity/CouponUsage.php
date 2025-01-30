<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponUsageRepository;

/**
* Entity that represents Coupon Usage
*
*/
#[ORM\Entity(repositoryClass: CouponUsageRepository::class)]
#[ORM\Table(name: 'oro_promotion_coupon_usage')]
class CouponUsage
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Coupon::class)]
    #[ORM\JoinColumn(name: 'coupon_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Coupon $coupon = null;

    #[ORM\ManyToOne(targetEntity: Promotion::class)]
    #[ORM\JoinColumn(name: 'promotion_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Promotion $promotion = null;

    #[ORM\ManyToOne(targetEntity: CustomerUser::class)]
    #[ORM\JoinColumn(name: 'customer_user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?CustomerUser $customerUser = null;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Coupon
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * @param Coupon $coupon
     * @return $this
     */
    public function setCoupon(Coupon $coupon)
    {
        $this->coupon = $coupon;

        return $this;
    }

    /**
     * @return Promotion
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
     * @return CustomerUser|null
     */
    public function getCustomerUser()
    {
        return $this->customerUser;
    }

    /**
     * @param CustomerUser|null $customerUser
     * @return $this
     */
    public function setCustomerUser(?CustomerUser $customerUser = null)
    {
        $this->customerUser = $customerUser;

        return $this;
    }
}
