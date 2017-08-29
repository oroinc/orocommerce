<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\RuleFiltration;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotionData;
use Oro\Bundle\PromotionBundle\RuleFiltration\CouponFiltrationService;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponFilterCouponData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponFilteredPromotionData;
use Oro\Bundle\RuleBundle\RuleFiltration\BasicRuleFiltrationService;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CouponFiltrationServiceTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadCouponFilterCouponData::class
        ]);
    }

    public function testGetFilteredRuleOwners()
    {
        $couponFiltrationService = new CouponFiltrationService(new BasicRuleFiltrationService());

        $promoCorrespondingSeveralAppliedDiscounts
            = $this->getReference(LoadCouponFilteredPromotionData::PROMO_CORRESPONDING_SEVERAL_APPLIED_DISCOUNTS);
        $promoCorrespondingOneAppliedDiscounts
            = $this->getReference(LoadCouponFilteredPromotionData::PROMO_CORRESPONDING_ONE_APPLIED_DISCOUNTS);
        $promoNotCorrespondingAppliedDiscounts
            = $this->getReference(LoadCouponFilteredPromotionData::PROMO_NOT_CORRESPONDING_APPLIED_DISCOUNTS);
        $promoWithoutDiscounts = $this->getReference(LoadCouponFilteredPromotionData::PROMO_WITHOUT_DISCOUNTS);

        // All AppliedPromotion models should not be filtered by CouponFiltrationService
        $appliedPromotion = new AppliedPromotionData();

        $filteredPromotions = $couponFiltrationService->getFilteredRuleOwners(
            [
                $promoCorrespondingSeveralAppliedDiscounts,
                $promoCorrespondingOneAppliedDiscounts,
                $promoNotCorrespondingAppliedDiscounts,
                $promoWithoutDiscounts,
                $appliedPromotion
            ],
            [
                ContextDataConverterInterface::APPLIED_COUPONS => new ArrayCollection([
                    $this->getReference(LoadCouponFilterCouponData::COUPON1),
                    $this->getReference(LoadCouponFilterCouponData::COUPON2),
                    $this->getReference(LoadCouponFilterCouponData::COUPON3),
                ])
            ]
        );

        $expected = [
            $promoCorrespondingSeveralAppliedDiscounts,
            $promoCorrespondingOneAppliedDiscounts,
            $promoWithoutDiscounts,
            $appliedPromotion
        ];

        $this->assertEquals($expected, $filteredPromotions);
    }
}
