<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Handler;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Exception\LogicException;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Symfony\Component\HttpFoundation\Request;

class CouponValidationHandlerTest extends AbstractCouponHandlerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getHandlerServiceName()
    {
        return 'oro_promotion.handler.coupon_validation_handler';
    }

    public function testHandleWhenNoCouponId()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Coupon id is not specified in request parameters');

        $request = new Request();
        $this->handler->handle($request);
    }

    public function testHandleWhenCouponDoesNotExistById()
    {
        $couponId = PHP_INT_MAX;
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Cannot find "%s" entity with id "%s"',
            Coupon::class,
            $couponId
        ));

        $request = new Request([], ['couponId' => $couponId]);
        $this->handler->handle($request);
    }

    public function testHandle()
    {
        $request = $this->getRequestWithCouponData([
            'entityClass' => Order::class,
            'entityId' => $this->getReference(LoadOrders::ORDER_2)->getId(),
        ]);
        $response = $this->handler->handle($request);
        self::assertJsonResponseStatusCodeEquals($response, 200);
        $jsonContent = json_decode($response->getContent(), true);
        self::assertTrue($jsonContent['success']);
        self::assertEmpty($jsonContent['errors']);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestWithCouponData(array $postData = [])
    {
        $postData['couponId'] = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL)->getId();

        return new Request([], $postData);
    }
}
