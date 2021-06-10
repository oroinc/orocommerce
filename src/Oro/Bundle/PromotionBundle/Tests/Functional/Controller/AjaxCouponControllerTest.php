<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Controller;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AjaxCouponControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                LoadCouponData::class,
                LoadOrders::class,
            ]
        );
    }

    public function testGetAddedCouponsTableAction()
    {
        /** @var Coupon $coupon1 */
        $coupon1 = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);
        /** @var Coupon $coupon2 */
        $coupon2 = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_promotion_get_added_coupons_table',
                ['addedCouponIds' => implode(',', [$coupon1->getId(), $coupon2->getId()])]
            )
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $jsonContent = json_decode($result->getContent(), true);
        static::assertStringContainsString('grid-container', $jsonContent);
        static::assertStringContainsString($coupon1->getCode(), $jsonContent);
        static::assertStringContainsString($coupon2->getCode(), $jsonContent);
    }

    public function testGetAddedCouponsTableActionWhenNoIds()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_promotion_get_added_coupons_table')
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $jsonContent = json_decode($result->getContent(), true);
        $this->assertEmpty($jsonContent);
    }

    public function testValidateCouponApplicabilityAction()
    {
        $this->client->request(
            'POST',
            $this->getUrl('oro_promotion_validate_coupon_applicability'),
            [
                'couponId' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getId(),
                'entityClass' => Order::class,
                'entityId' => $this->getReference(LoadOrders::ORDER_1)->getId(),
            ]
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $jsonContent = json_decode($result->getContent(), true);
        $this->assertFalse($jsonContent['success']);
    }

    public function testGetAppliedCouponsData()
    {
        /** @var Coupon $coupon1 */
        $coupon1 = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);
        /** @var Coupon $coupon2 */
        $coupon2 = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_promotion_get_applied_coupons_data',
                ['couponIds' => implode(',', [$coupon1->getId(), $coupon2->getId()])]
            )
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $appliedCouponsData = json_decode($result->getContent(), true);
        usort($appliedCouponsData, static function ($a, $b) {
            return $b['sourceCouponId'] <=> $a['sourceCouponId'];
        });
        $this->assertCount(2, $appliedCouponsData);
        $this->assertEquals(
            [
                'couponCode' => $coupon1->getCode(),
                'sourcePromotionId' => $coupon1->getPromotion()->getId(),
                'sourceCouponId' => $coupon1->getId(),
            ],
            reset($appliedCouponsData)
        );
        $this->assertEquals(
            [
                'couponCode' => $coupon2->getCode(),
                'sourcePromotionId' => $coupon2->getPromotion()->getId(),
                'sourceCouponId' => $coupon2->getId(),
            ],
            end($appliedCouponsData)
        );
    }

    public function testGetAppliedCouponsDataWhenNoIds()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_promotion_get_applied_coupons_data')
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $appliedCouponsData = json_decode($result->getContent(), true);
        $this->assertEmpty($appliedCouponsData);
    }
}
