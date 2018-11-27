<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CurrencyBundle\Rounding\PriceRoundingService;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderDiscounts;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;

/**
 * @dbIsolationPerTest
 */
class OrderDiscountTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadOrderDiscounts::class,
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'orderdiscounts']);

        $this->assertResponseContains('discount_get_list.yml', $response);
    }

    public function testGet()
    {
        /** @var OrderDiscount $discount */
        $discount = $this->getReference(LoadOrderDiscounts::REFERENCE_DISCOUNT_PERCENT);

        $response = $this->get(
            ['entity' => 'orderdiscounts', 'id' => $discount->getId()]
        );

        $this->assertResponseContains('discount_get.yml', $response);
    }

    public function testGetOrderSubResource()
    {
        /** @var OrderDiscount $discount */
        $discount = $this->getReference(LoadOrderDiscounts::REFERENCE_DISCOUNT_AMOUNT);

        $response = $this->getSubresource(
            ['entity' => 'orderdiscounts', 'id' => $discount->getId(), 'association' => 'order']
        );

        $order = json_decode($response->getContent(), true)['data'];

        self::assertEquals($this->getReferenceOrder()->getId(), $order['id']);
    }

    public function testGetOrderRelationship()
    {
        /** @var OrderDiscount $discount */
        $discount = $this->getReference(LoadOrderDiscounts::REFERENCE_DISCOUNT_AMOUNT);

        $response = $this->getRelationship(
            ['entity' => 'orderdiscounts', 'id' => $discount->getId(), 'association' => 'order']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => (string)$this->getReferenceOrder()->getId()]],
            $response
        );
    }

    public function testCreate()
    {
        $description = 'New Discount';
        $percent = 0.201;
        $amount = 180;

        $this->post(
            ['entity' => 'orderdiscounts'],
            'discount_create.yml'
        );

        /** @var OrderDiscount $discount */
        $discount = $this->getEntityManager()
            ->getRepository(OrderDiscount::class)
            ->findOneBy(['description' => $description]);
        /** @var Order $order */
        $order = $this->getEntityManager()
            ->getRepository(Order::class)
            ->find($this->getReferenceOrder()->getId());

        self::assertSame($description, $discount->getDescription());
        self::assertEquals($percent, $discount->getPercent());
        self::assertEquals($amount, $discount->getAmount());
        self::assertSame(OrderDiscount::TYPE_AMOUNT, $discount->getType());

        $discountAmount = $discount->getPercent() * $order->getSubtotal() + $discount->getAmount();

        $roundingService = new PriceRoundingService($this->getContainer()->get('oro_config.manager'));
        self::assertSame($roundingService->round($discountAmount), (float)$order->getTotalDiscounts()->getValue());
    }

    public function testUpdateDescription()
    {
        $newDescription = 'new description';

        /** @var OrderDiscount $discount */
        $discount = $this->getReference(LoadOrderDiscounts::REFERENCE_DISCOUNT_AMOUNT);

        $this->patch(
            ['entity' => 'orderdiscounts', 'id' => $discount->getId()],
            [
                'data' => [
                    'type' => 'orderdiscounts',
                    'id' => (string)$discount->getId(),
                    'attributes' => [
                        'description' => $newDescription,
                    ],
                ],
            ]
        );

        /** @var OrderDiscount $updatedDiscount */
        $updatedDiscount = $this->getEntityManager()
            ->getRepository(OrderDiscount::class)
            ->find($discount->getId());

        self::assertSame($newDescription, $updatedDiscount->getDescription());
    }

    public function testUpdateAmount()
    {
        $newAmount = 300;

        /** @var OrderDiscount $discount */
        $discount = $this->getReference(LoadOrderDiscounts::REFERENCE_DISCOUNT_AMOUNT);

        $this->patch(
            ['entity' => 'orderdiscounts', 'id' => $discount->getId()],
            [
                'data' => [
                    'type' => 'orderdiscounts',
                    'id' => (string)$discount->getId(),
                    'attributes' => [
                        'amount' => $newAmount,
                    ],
                ],
            ]
        );

        /** @var OrderDiscount $updatedDiscount */
        $updatedDiscount = $this->getEntityManager()
            ->getRepository(OrderDiscount::class)
            ->find($discount->getId());

        self::assertEquals($newAmount, (float)$updatedDiscount->getOrder()->getTotalDiscounts()->getValue());
    }

    public function testPatchOrderRelationship()
    {
        /** @var OrderDiscount $discount */
        $discount = $this->getReference(LoadOrderDiscounts::REFERENCE_DISCOUNT_AMOUNT);

        /** @var Order $order2 */
        $order2 = $this->getReference(LoadOrders::MY_ORDER);

        $this->patchRelationship(
            ['entity' => 'orderdiscounts', 'id' => (string)$discount->getId(), 'association' => 'order'],
            [
                'data' => [
                    'type' => $this->getEntityType(Order::class),
                    'id' => (string)$order2->getId(),
                ],
            ]
        );

        /** @var OrderDiscount $updatedDiscount */
        $updatedDiscount = $this->getEntityManager()
            ->getRepository(OrderDiscount::class)
            ->find($discount->getId());

        self::assertEquals($order2->getId(), $updatedDiscount->getOrder()->getId());
    }

    public function testDeleteByFilter()
    {
        /** @var OrderDiscount $discount */
        $discount = $this->getReference(LoadOrderDiscounts::REFERENCE_DISCOUNT_AMOUNT);
        $discountId = $discount->getId();

        $this->cdelete(
            ['entity' => 'orderdiscounts'],
            ['filter' => ['id' => $discountId]]
        );

        $removedDiscount = $this->getEntityManager()
            ->getRepository(OrderDiscount::class)
            ->find($discountId);

        self::assertNull($removedDiscount);
    }

    /**
     * @return Order
     */
    private function getReferenceOrder()
    {
        return $this->getReference(LoadOrders::ORDER_1);
    }
}
