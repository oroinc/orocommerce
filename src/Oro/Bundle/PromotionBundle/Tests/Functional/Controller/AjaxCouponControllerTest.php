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
    protected function setUp()
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
        $coupon1 = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL);
        /** @var Coupon $coupon2 */
        $coupon2 = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL);

        $this->client->request(
            'POST',
            $this->getUrl('oro_promotion_get_added_coupons_table'),
            ['addedCouponIds' => implode(',', [$coupon1->getId(), $coupon2->getId()])]
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $jsonContent = json_decode($result->getContent(), true);
        $this->assertContains('grid-container', $jsonContent);
        $this->assertContains($coupon1->getCode(), $jsonContent);
        $this->assertContains($coupon2->getCode(), $jsonContent);
    }

    public function testGetAddedCouponsTableActionWhenNoIds()
    {
        $this->client->request(
            'POST',
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
                'couponId' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL)->getId(),
                'entityClass' => Order::class,
                'entityId' => $this->getReference(LoadOrders::ORDER_1)->getId(),
            ]
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $jsonContent = json_decode($result->getContent(), true);
        $this->assertFalse($jsonContent['success']);
    }
}
