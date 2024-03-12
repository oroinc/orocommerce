<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\ValidationService;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\ValidationService\OrganizationCouponValidator;

class OrganizationCouponValidatorTest extends \PHPUnit\Framework\TestCase
{
    public function testGetViolationWithCouponFromAnotherOrganization(): void
    {
        $organization1 = new Organization();
        $organization1->setId(1);

        $organization2 = new Organization();
        $organization2->setId(2);

        $coupon = new Coupon();
        $coupon->setOrganization($organization1);

        $checkout = new Checkout();
        $checkout->setOrganization($organization2);

        self::assertEquals(
            ['oro.promotion.coupon.violation.invalid_coupon_code'],
            (new OrganizationCouponValidator())->getViolationMessages($coupon, $checkout)
        );
    }

    public function testGetViolationWithCouponFromTheSameOrganization(): void
    {
        $organization = new Organization();
        $organization->setId(1);

        $coupon = new Coupon();
        $coupon->setOrganization($organization);

        $checkout = new Checkout();
        $checkout->setOrganization($organization);

        self::assertEmpty((new OrganizationCouponValidator())->getViolationMessages($coupon, $checkout));
    }
}
