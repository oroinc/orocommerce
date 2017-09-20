<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Handler;

use Oro\Bundle\EntityBundle\Exception\EntityNotFoundException;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Exception\LogicException;
use Oro\Bundle\PromotionBundle\Handler\CouponValidationHandler;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\PromotionBundle\ValidationService\CouponApplicabilityValidationService;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class CouponValidationHandlerTest extends WebTestCase
{
    /**
     * @var CouponValidationHandler
     */
    private $handler;

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
                LoadOrderLineItemData::class,
            ]
        );
        $this->handler = static::getContainer()->get('oro_promotion.handler.coupon_validation_handler');
    }

    public function testHandleWhenNoCouponId()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Coupon id is not specified in request parameters');

        $request = $this->getRequest();
        $this->handler->handle($request);
    }

    public function testHandleWhenNoEntityClass()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Entity class is not specified in request parameters');

        $request = $this->getRequest([
            'couponId' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL)->getId(),
        ]);
        $this->handler->handle($request);
    }

    public function testHandleWhenUnknownEntityClass()
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Cannot resolve entity class "SomeBundle\SomeUnknownClass"');

        $request = $this->getRequest([
            'couponId' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL)->getId(),
            'entityClass' => 'SomeBundle\SomeUnknownClass',
        ]);
        $this->handler->handle($request);
    }

    public function testHandleWhenNoEntityId()
    {
        $request = $this->getRequest([
            'couponId' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL)->getId(),
            'entityClass' => Order::class,
        ]);
        $response = $this->handler->handle($request);
        $this->assertJsonResponseStatusCodeEquals($response, 200);
        $jsonContent = json_decode($response->getContent(), true);
        $this->assertFalse($jsonContent['success']);
    }

    public function testHandleWhenNoApplicableEntity()
    {
        $request = $this->getRequest([
            'couponId' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL)->getId(),
            'entityClass' => Order::class,
            'entityId' => $this->getReference(LoadOrders::ORDER_1)->getId(),
        ]);
        $response = $this->handler->handle($request);
        $this->assertJsonResponseStatusCodeEquals($response, 200);
        $jsonContent = json_decode($response->getContent(), true);
        $this->assertFalse($jsonContent['success']);
        $this->assertEquals(
            [CouponApplicabilityValidationService::MESSAGE_PROMOTION_NOT_APPLICABLE],
            $jsonContent['errors']
        );
    }

    public function testHandle()
    {
        $request = $this->getRequest([
            'couponId' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL)->getId(),
            'entityClass' => Order::class,
            'entityId' => $this->getReference(LoadOrders::ORDER_2)->getId(),
        ]);
        $response = $this->handler->handle($request);
        $this->assertJsonResponseStatusCodeEquals($response, 200);
        $jsonContent = json_decode($response->getContent(), true);
        $this->assertTrue($jsonContent['success']);
        $this->assertEmpty($jsonContent['errors']);
    }

    /**
     * @param array $postData
     * @return Request
     */
    private function getRequest(array $postData = [])
    {
        return new Request([], $postData);
    }
}
