<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Manager;

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
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([
            LoadPromotionData::class,
        ]);
    }

    public function testGenerateAndSave()
    {
        /** @var Promotion $promotion */
        $promotion = $this->getReference(LoadPromotionData::ORDER_AMOUNT_PROMOTION);
        /** @var BusinessUnit $businessUnit */
        $businessUnit = $this->getDoctrineHelper()
            ->getEntityRepository(BusinessUnit::class)
            ->findOneBy(['name' => LoadOrganizationAndBusinessUnitData::MAIN_BUSINESS_UNIT]);

        $options = new CouponGenerationOptions();
        $options->setOwner($businessUnit);
        $options->setExpirationDate(new \DateTime('01-01-2020 12:00:00'));
        $options->setPromotion($promotion);
        $options->setUsesPerCoupon(22);
        $options->setUsesPerUser(null);

        $options->setCouponQuantity(200);
        $options->setCodeLength(1);
        $options->setCodeType(CodeGenerationOptions::NUMERIC_CODE_TYPE);

        /** @var CouponGenerator $generator */
        $generator = $this->getContainer()->get('oro_promotion.coupon_generation.coupon');
        $statistic = $generator->generateAndSave($options);

        $this->assertEquals([1 => 10, 2 => 100, 3 => 90], $statistic);
        $this->assertEquals(200, $this->getDoctrineHelper()->getEntityManager(Coupon::class)->createQuery(
            'SELECT COUNT(coupon) FROM Oro\Bundle\PromotionBundle\Entity\Coupon AS coupon'
        )->getSingleScalarResult());

        /** @var Coupon $coupon */
        $coupon = $this->getDoctrineHelper()->getEntityRepository(Coupon::class)->findOneBy([]);
        $this->assertRegExp('/^[0-9]{1,3}$/', $coupon->getCode());
        $this->assertEquals($options->getOwner()->getId(), $coupon->getOwner()->getId());
        $this->assertEquals($options->getPromotion()->getId(), $coupon->getPromotion()->getId());
        $this->assertEquals($options->getUsesPerCoupon(), $coupon->getUsesPerCoupon());
        $this->assertEquals($options->getUsesPerUser(), $coupon->getUsesPerUser());
        $this->assertEquals($options->getExpirationDate(), $coupon->getValidUntil());
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
