<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\PromotionBundle\Entity\CouponUsage;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponUsageData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CouponUsageRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadCouponUsageData::class]);
    }

    public function testGetCouponUsageCount()
    {
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);

        $actualCount = $this->getContainer()->get('doctrine')->getRepository(CouponUsage::class)
            ->getCouponUsageCount($coupon);

        $this->assertEquals(3, $actualCount);
    }

    /**
     * @dataProvider getCouponUsageByCustomerUserCountDataProvider
     */
    public function testGetCouponUsageByCustomerUserCount(string $customerUser, int $expectedCouponUsageCount)
    {
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);
        $customerUser = $this->getReference($customerUser);

        $actualCount = $this->getContainer()->get('doctrine')->getRepository(CouponUsage::class)
            ->getCouponUsageCount($coupon, $customerUser);

        $this->assertEquals($expectedCouponUsageCount, $actualCount);
    }

    public function getCouponUsageByCustomerUserCountDataProvider(): array
    {
        return [
            [
                'customerUser' => LoadCustomerUserData::EMAIL,
                'expectedCouponUsageCount' => 2
            ],
            [
                'customerUser' => LoadCustomerUserData::ANONYMOUS_EMAIL,
                'expectedCouponUsageCount' => 0
            ],
        ];
    }
}
