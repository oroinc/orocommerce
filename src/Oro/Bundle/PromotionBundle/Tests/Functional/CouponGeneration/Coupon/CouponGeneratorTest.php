<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\CouponGeneration\Coupon;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\PromotionBundle\CouponGeneration\Coupon\CouponGenerator;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CouponGeneratorTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([
            LoadPromotionData::class,
        ]);
    }

    public function testGenerateAndSave()
    {
        /** @var Promotion $promotion */
        $promotion = $this->getReference(LoadPromotionData::ORDER_PERCENT_PROMOTION);
        /** @var BusinessUnit $businessUnit */
        $businessUnit = $this->getDoctrineHelper()
            ->getEntityRepository(BusinessUnit::class)
            ->findOneBy(['name' => LoadOrganizationAndBusinessUnitData::MAIN_BUSINESS_UNIT]);

        $options = new CouponGenerationOptions();
        $options->setOwner($businessUnit);
        $options->setValidFrom(new \DateTime('01-01-2010 12:00:00'));
        $options->setValidUntil(new \DateTime('01-01-2020 12:00:00'));
        $options->setPromotion($promotion);
        $options->setEnabled(true);
        $options->setUsesPerCoupon(22);
        $options->setUsesPerPerson(null);

        $options->setCouponQuantity(200);
        $options->setCodeLength(1);
        $options->setCodeType(CodeGenerationOptions::NUMERIC_CODE_TYPE);

        /** @var CouponGenerator $generator */
        $generator = $this->getContainer()->get('oro_promotion.coupon_generation.coupon');
        $generator->generateAndSave($options);

        $generatedCoupons = $this->getDoctrineHelper()->getEntityRepository(Coupon::class)->findAll();
        $this->assertCount(200, $generatedCoupons);

        $statistic = [];
        /** @var Coupon $coupon */
        foreach ($generatedCoupons as $coupon) {
            $codeLength = strlen($coupon->getCode());

            if (!isset($statistic[$codeLength])) {
                $statistic[$codeLength] = 0;
            }

            $statistic[$codeLength]++;
        }

        $this->assertEquals([1 => 10, 2 => 100, 3 => 90], $statistic);

        /** @var Coupon $coupon */
        $coupon = reset($generatedCoupons);
        $this->assertMatchesRegularExpression('/^[0-9]{1,3}$/', $coupon->getCode());
        $this->assertEquals(strtoupper($coupon->getCode()), $coupon->getCodeUppercase());
        $this->assertEquals($options->getOwner(), $coupon->getOwner());
        $this->assertEquals($options->isEnabled(), $coupon->isEnabled());
        $this->assertEquals($options->getPromotion(), $coupon->getPromotion());
        $this->assertEquals($options->getUsesPerCoupon(), $coupon->getUsesPerCoupon());
        $this->assertEquals($options->getUsesPerPerson(), $coupon->getUsesPerPerson());
        $this->assertEquals($options->getValidFrom(), $coupon->getValidFrom());
        $this->assertEquals($options->getValidUntil(), $coupon->getValidUntil());
        $this->assertInstanceOf(\DateTime::class, $coupon->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $coupon->getUpdatedAt());
    }

    /**
     * @return DoctrineHelper
     */
    protected function getDoctrineHelper()
    {
        return $this->getContainer()->get('oro_entity.doctrine_helper');
    }
}
