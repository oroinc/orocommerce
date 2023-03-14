<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Handler;

use Oro\Bundle\EntityBundle\Exception\EntityNotFoundException;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Exception\LogicException;
use Oro\Bundle\PromotionBundle\Handler\AbstractCouponHandler;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\PromotionBundle\ValidationService\CouponApplicabilityValidationService;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

abstract class AbstractCouponHandlerTestCase extends WebTestCase
{
    use RolePermissionExtension;

    /**
     * @var AbstractCouponHandler
     */
    protected $handler;

    /**
     * @var array
     */
    protected $fixturesToLoad = [
        LoadCouponData::class,
        LoadOrderLineItemData::class
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->loadFixtures($this->fixturesToLoad);
        $this->handler = static::getContainer()->get($this->getHandlerServiceName());

        static::getContainer()->get('security.token_storage')->setToken($this->getToken());
        $this->updateRolePermission($this->getRole(), Order::class, AccessLevel::GLOBAL_LEVEL, 'EDIT');
    }

    public function testHandleWhenNoEntityClass()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Entity class is not specified in request parameters');

        $request = $this->getRequestWithCouponData();
        $this->handler->handle($request);
    }

    public function testHandleWhenUnknownEntityClass()
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Cannot resolve entity class "SomeBundle\SomeUnknownClass"');

        $request = $this->getRequestWithCouponData([
            'entityClass' => 'SomeBundle\SomeUnknownClass',
        ]);
        $this->handler->handle($request);
    }

    public function testHandleWhenEntityDoesNotImplementNeededInterface()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Entity should have is_coupon_aware entity config');

        $request = $this->getRequestWithCouponData([
            'entityClass' => Promotion::class,
        ]);
        $this->handler->handle($request);
    }

    public function testHandleWhenEntityDoesNotHaveNeededPermissions()
    {
        $this->expectException(AccessDeniedException::class);
        $this->updateRolePermission($this->getRole(), Order::class, AccessLevel::NONE_LEVEL, 'EDIT');

        $request = $this->getRequestWithCouponData([
            'entityClass' => Order::class,
        ]);
        $this->handler->handle($request);
    }

    public function testHandleWhenNoEntityId()
    {
        $request = $this->getRequestWithCouponData([
            'entityClass' => Order::class,
        ]);
        $response = $this->handler->handle($request);

        self::assertJsonResponseStatusCodeEquals($response, 200);
        $jsonContent = json_decode($response->getContent(), true);
        self::assertFalse($jsonContent['success']);
    }

    public function testHandleWhenNoApplicableEntity()
    {
        $request = $this->getRequestWithCouponData([
            'entityClass' => Order::class,
            'entityId' => $this->getReference(LoadOrders::ORDER_1)->getId(),
        ]);
        $response = $this->handler->handle($request);

        self::assertJsonResponseStatusCodeEquals($response, 200);
        $jsonContent = json_decode($response->getContent(), true);
        self::assertFalse($jsonContent['success']);
        self::assertEquals(
            [CouponApplicabilityValidationService::MESSAGE_PROMOTION_NOT_APPLICABLE],
            $jsonContent['errors']
        );
    }

    /**
     * @return string
     */
    abstract protected function getRole();

    /**
     * @return TokenInterface
     */
    abstract protected function getToken();

    /**
     * @return string
     */
    abstract protected function getHandlerServiceName();

    /**
     * @param array $postData
     * @return Request
     */
    abstract protected function getRequestWithCouponData(array $postData = []);
}
