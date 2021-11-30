<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Handler;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Exception\LogicException;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class CouponValidationHandlerTest extends AbstractCouponHandlerTestCase
{
    /**
     * {@inheritdoc}Oro\Bundle\CommerceCrmEnterpriseTestBundle\Tests\Functional\BackendQueriesTest
     */
    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        parent::setUp();
        static::getContainer()->get('request_stack')
            ->push(Request::create($this->getUrl('oro_promotion_validate_coupon_applicability')));

        $this->loadFixtures([]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getToken()
    {
        $managerRegistry = self::getContainer()->get('doctrine');

        /** @var User $user */
        $user = $managerRegistry
            ->getRepository(User::class)
            ->findOneBy(['email' => self::AUTH_USER]);

        return new UsernamePasswordOrganizationToken(
            $user,
            false,
            'main',
            $user->getOrganization(),
            $user->getRoles()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getRole()
    {
        return 'ROLE_ADMINISTRATOR';
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
        $postData['couponId'] = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)
            ->getId();

        return new Request([], $postData);
    }
}
