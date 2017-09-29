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
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractCouponHandlerTestCase extends WebTestCase
{
    /**
     * @var AbstractCouponHandler
     */
    protected $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->loadFixtures([
            LoadCouponData::class,
            LoadOrderLineItemData::class,
        ]);
        $this->handler = static::getContainer()->get($this->getHandlerServiceName());
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
        $this->expectExceptionMessage('Entity should be instance of AppliedCouponsAwareInterface');

        $request = $this->getRequestWithCouponData([
            'entityClass' => Promotion::class,
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
    abstract protected function getHandlerServiceName();

    /**
     * @param array $postData
     * @return Request
     */
    abstract protected function getRequestWithCouponData(array $postData = []);
}
