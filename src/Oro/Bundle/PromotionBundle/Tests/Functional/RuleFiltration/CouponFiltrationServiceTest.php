<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\RuleFiltration;

use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
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
        $couponFiltrationService = new CouponFiltrationService(
            new BasicRuleFiltrationService(),
            $this->getContainer()->get('oro_promotion.validation_service.coupon_validation')
        );

        $promoCorrespondingSeveralAppliedDiscounts
            = $this->getReference(LoadCouponFilteredPromotionData::PROMO_CORRESPONDING_SEVERAL_APPLIED_DISCOUNTS);
        $promoCorrespondingOneAppliedDiscounts
            = $this->getReference(LoadCouponFilteredPromotionData::PROMO_CORRESPONDING_ONE_APPLIED_DISCOUNTS);
        $promoNotCorrespondingAppliedDiscounts
            = $this->getReference(LoadCouponFilteredPromotionData::PROMO_NOT_CORRESPONDING_APPLIED_DISCOUNTS);
        $promoWithoutDiscounts = $this->getReference(LoadCouponFilteredPromotionData::PROMO_WITHOUT_DISCOUNTS);

        $filteredPromotions = $couponFiltrationService->getFilteredRuleOwners(
            [
                $promoCorrespondingSeveralAppliedDiscounts,
                $promoCorrespondingOneAppliedDiscounts,
                $promoNotCorrespondingAppliedDiscounts,
                $promoWithoutDiscounts
            ],
            [
                ContextDataConverterInterface::APPLIED_COUPONS => [
                    $this->getReference(LoadCouponFilterCouponData::COUPON1),
                    $this->getReference(LoadCouponFilterCouponData::COUPON2),
                    $this->getReference(LoadCouponFilterCouponData::COUPON3),
                ]
            ]
        );

        $expected = [
            $promoCorrespondingSeveralAppliedDiscounts,
            $promoCorrespondingOneAppliedDiscounts,
            $promoWithoutDiscounts

        ];

        $this->assertEquals($expected, $filteredPromotions);
    }
}
