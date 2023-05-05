<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Handler;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\PromotionBundle\Exception\LogicException;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCheckoutData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
class FrontendCouponHandlerTest extends AbstractCouponHandlerTestCase
{
    use ConfigManagerAwareTestTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->fixturesToLoad[] = LoadCheckoutData::class;
        $this->fixturesToLoad[] = LoadCombinedProductPrices::class;
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->client->useHashNavigation(true);

        parent::setUp();
        static::getContainer()->get('request_stack')
            ->push(Request::create($this->getUrl('oro_promotion_frontend_add_coupon')));
    }

    /**
     * {@inheritdoc}
     */
    protected function getToken()
    {
        $user = self::getContainer()->get('doctrine')
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);

        return new UsernamePasswordOrganizationToken(
            $user,
            false,
            'k',
            $user->getOrganization(),
            $user->getUserRoles()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getRole()
    {
        return 'ROLE_FRONTEND_ADMINISTRATOR';
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

    public function testHandleWhenNoAppliedPromotionInterface()
    {
        /** @var Order $entity */
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

        $expectedAppliedCoupons = $entity->getAppliedCoupons()->toArray();
        self::assertCount(0, $expectedAppliedCoupons);
    }

    public function testHandle()
    {
        /** @var Checkout $entity */
        $entity = $this->getReference(LoadCheckoutData::PROMOTION_CHECKOUT_1);
        $request = $this->getRequestWithCouponData([
            'entityClass' => Checkout::class,
            'entityId' => $entity->getId(),
        ]);
        $response = $this->handler->handle($request);

        self::assertJsonResponseStatusCodeEquals($response, 200);
        $jsonContent = json_decode($response->getContent(), true);
        self::assertTrue($jsonContent['success']);
        self::assertEmpty($jsonContent['errors']);

        $expectedAppliedCoupons = array_values($entity->getAppliedCoupons()->toArray());
        self::assertCount(1, $expectedAppliedCoupons);
        self::getContainer()->get('doctrine')->getManagerForClass(Checkout::class)->refresh($entity);

        $appliedCoupons = $entity->getAppliedCoupons()->toArray();
        self::assertEquals($expectedAppliedCoupons, $appliedCoupons);
    }

    public function testHandleCaseSensitive()
    {
        /** @var Checkout $entity */
        $entity = $this->getReference(LoadCheckoutData::PROMOTION_CHECKOUT_1);
        $code = strtoupper($this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getCode());
        $postData = [
            'entityClass' => Checkout::class,
            'entityId' => $entity->getId(),
            'couponCode' => $code
        ];

        $request = new Request([], $postData);
        $response = $this->handler->handle($request);

        self::assertJsonResponseStatusCodeEquals($response, 200);
        $jsonContent = json_decode($response->getContent(), true);
        self::assertFalse($jsonContent['success']);
        self::assertEquals('oro.promotion.coupon.violation.invalid_coupon_code', reset($jsonContent['errors']));
    }

    public function testHandleCaseInsensitive()
    {
        $configManager = self::getConfigManager('global');
        $savedState = $configManager->get('oro_promotion.case_insensitive_coupon_search');
        $configManager->set('oro_promotion.case_insensitive_coupon_search', true);
        $configManager->flush();

        /** @var Checkout $entity */
        $entity = $this->getReference(LoadCheckoutData::PROMOTION_CHECKOUT_1);
        $code = strtoupper($this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getCode());
        $postData = [
            'entityClass' => Checkout::class,
            'entityId' => $entity->getId(),
            'couponCode' => $code
        ];

        $request = new Request([], $postData);
        $response = $this->handler->handle($request);

        self::assertJsonResponseStatusCodeEquals($response, 200);
        $jsonContent = json_decode($response->getContent(), true);

        self::assertTrue($jsonContent['success']);
        self::assertEmpty($jsonContent['errors']);

        $expectedAppliedCoupons = array_values($entity->getAppliedCoupons()->toArray());
        self::assertCount(1, $expectedAppliedCoupons);
        self::getContainer()->get('doctrine')->getManagerForClass(Checkout::class)->refresh($entity);

        $appliedCoupons = $entity->getAppliedCoupons()->toArray();
        self::assertEquals($expectedAppliedCoupons, $appliedCoupons);

        $configManager->set('oro_promotion.case_insensitive_coupon_search', $savedState);
        $configManager->flush();
    }

    public function testHandleShippingPromotionForShoppingList()
    {
        /** @var Checkout $entity */
        $entity = $this->getReference(LoadCheckoutData::PROMOTION_CHECKOUT_1);
        $request = $this->getRequestWithEuroCouponData([
            'entityClass' => Checkout::class,
            'entityId' => $entity->getId(),
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
    private function getRequestWithEuroCouponData(array $postData = [])
    {
        $postData['couponCode'] = $this
            ->getReference(LoadCouponData::COUPON_WITH_SHIPPING_PROMO_AND_VALID_UNTIL)
            ->getCode();

        return new Request([], $postData);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestWithCouponData(array $postData = [])
    {
        $postData['couponCode'] = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)
            ->getCode();

        return new Request([], $postData);
    }
}
