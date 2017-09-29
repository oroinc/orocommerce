<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Handler;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Exception\LogicException;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Symfony\Component\HttpFoundation\Request;

class FrontendCouponHandlerTest extends AbstractCouponHandlerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->client->useHashNavigation(true);

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getHandlerServiceName()
    {
        return 'oro_promotion.handler.frontend_coupon_handler';
    }

    public function testHandleWhenNoCouponCode()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Coupon code is not specified in request parameters');

        $request = new Request();
        $this->handler->handle($request);
    }

    public function testHandleWhenNoCouponWithCodeFromRequest()
    {
        $request = new Request([], ['couponCode' => 'wrong-code']);
        $response = $this->handler->handle($request);

        self::assertJsonResponseStatusCodeEquals($response, 200);
        $jsonContent = json_decode($response->getContent(), true);
        self::assertFalse($jsonContent['success']);
        self::assertNotEmpty($jsonContent['errors']);
        self::assertEquals('oro.promotion.coupon.violation.invalid_coupon_code', reset($jsonContent['errors']));
    }

    public function testHandle()
    {
        /** @var AppliedCouponsAwareInterface|Order $entity */
        $entity = $this->getReference(LoadOrders::ORDER_2);
        $request = $this->getRequestWithCouponData([
            'entityClass' => Order::class,
            'entityId' => $entity->getId(),
        ]);
        $response = $this->handler->handle($request);

        self::assertJsonResponseStatusCodeEquals($response, 200);
        $jsonContent = json_decode($response->getContent(), true);
        self::assertTrue($jsonContent['success']);
        self::assertEmpty($jsonContent['errors']);

        $expectedAppliedCoupons = array_values($entity->getAppliedCoupons()->toArray());
        self::assertCount(1, $expectedAppliedCoupons);
        self::getContainer()->get('doctrine')->getManagerForClass(Order::class)->refresh($entity);

        $appliedCoupons = $entity->getAppliedCoupons()->toArray();
        self::assertEquals($expectedAppliedCoupons, $appliedCoupons);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestWithCouponData(array $postData = [])
    {
        $postData['couponCode'] = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL)->getCode();

        return new Request([], $postData);
    }
}
