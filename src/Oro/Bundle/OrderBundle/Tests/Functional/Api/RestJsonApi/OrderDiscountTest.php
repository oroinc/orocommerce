<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CurrencyBundle\Test\Functional\RoundPriceExtension;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;

/**
 * @dbIsolationPerTest
 */
class OrderDiscountTest extends RestJsonApiTestCase
{
    use RoundPriceExtension;

    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroOrderBundle/Tests/Functional/DataFixtures/order_discounts.yml'
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'orderdiscounts']);

        $this->assertResponseContains('cget_discount.yml', $response);
    }

    public function testGet()
    {
        $discountId = $this->getReference('order_discount.percent')->getId();

        $response = $this->get(
            ['entity' => 'orderdiscounts', 'id' => (string)$discountId]
        );

        $this->assertResponseContains('get_discount.yml', $response);
    }

    public function testCreate()
    {
        $orderId = $this->getReference(LoadOrders::ORDER_1)->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'attributes'    => [
                    'description'       => 'New Discount',
                    'percent'           => 0.201,
                    'amount'            => 180,
                    'orderDiscountType' => OrderDiscount::TYPE_AMOUNT
                ],
                'relationships' => [
                    'order' => [
                        'data' => [
                            'type' => 'orders',
                            'id'   => (string)$orderId
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'orderdiscounts'],
            $data
        );

        $discountId = (int)$this->getResourceId($response);
        $responseContent = $data;
        $responseContent['data']['id'] = (string)$discountId;
        $responseContent['data']['attributes']['amount'] = '180';
        $this->assertResponseContains($responseContent, $response);

        /** @var OrderDiscount $discount */
        $discount = $this->getEntityManager()->find(OrderDiscount::class, $discountId);
        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);

        self::assertEquals('New Discount', $discount->getDescription());
        self::assertSame(0.201, $discount->getPercent());
        self::assertSame('180.0000', $discount->getAmount());
        self::assertEquals(OrderDiscount::TYPE_AMOUNT, $discount->getType());
        self::assertSame(
            $this->roundPrice($discount->getPercent() * $order->getSubtotal() + $discount->getAmount()),
            (float)$order->getTotalDiscounts()->getValue()
        );
    }

    public function testUpdateDescription()
    {
        $discountId = $this->getReference('order_discount.amount')->getId();

        $this->patch(
            ['entity' => 'orderdiscounts', 'id' => (string)$discountId],
            [
                'data' => [
                    'type'       => 'orderdiscounts',
                    'id'         => (string)$discountId,
                    'attributes' => [
                        'description' => 'New Description'
                    ]
                ]
            ]
        );

        /** @var OrderDiscount $discount */
        $discount = $this->getEntityManager()->find(OrderDiscount::class, $discountId);
        self::assertEquals('New Description', $discount->getDescription());
    }

    public function testUpdateAmount()
    {
        $discountId = $this->getReference('order_discount.amount')->getId();

        $this->patch(
            ['entity' => 'orderdiscounts', 'id' => (string)$discountId],
            [
                'data' => [
                    'type'       => 'orderdiscounts',
                    'id'         => (string)$discountId,
                    'attributes' => [
                        'amount' => 300
                    ]
                ]
            ]
        );

        /** @var OrderDiscount $discount */
        $discount = $this->getEntityManager()->find(OrderDiscount::class, $discountId);
        self::assertSame(300.0, (float)$discount->getOrder()->getTotalDiscounts()->getValue());
    }

    public function testDeleteList()
    {
        $discountId = $this->getReference('order_discount.amount')->getId();

        $this->cdelete(
            ['entity' => 'orderdiscounts'],
            ['filter' => ['id' => $discountId]]
        );

        $discount = $this->getEntityManager()->find(OrderDiscount::class, $discountId);
        self::assertTrue(null === $discount);
    }

    public function testGetSubResourceForOrder()
    {
        /** @var OrderDiscount $discount */
        $discount = $this->getReference('order_discount.amount');
        $discountId = $discount->getId();
        $orderId = $discount->getOrder()->getId();
        $orderPoNumber = $discount->getOrder()->getPoNumber();

        $response = $this->getSubresource(
            ['entity' => 'orderdiscounts', 'id' => (string)$discountId, 'association' => 'order']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => (string)$orderId,
                    'attributes' => [
                        'poNumber' => $orderPoNumber
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForOrder()
    {
        /** @var OrderDiscount $discount */
        $discount = $this->getReference('order_discount.amount');
        $discountId = $discount->getId();
        $orderId = $discount->getOrder()->getId();

        $response = $this->getRelationship(
            ['entity' => 'orderdiscounts', 'id' => (string)$discountId, 'association' => 'order']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => (string)$orderId]],
            $response
        );
    }

    public function testUpdateRelationshipForOrder()
    {
        $discountId = $this->getReference('order_discount.amount')->getId();
        $targetOrderId = $this->getReference(LoadOrders::MY_ORDER)->getId();

        $this->patchRelationship(
            ['entity' => 'orderdiscounts', 'id' => (string)$discountId, 'association' => 'order'],
            [
                'data' => [
                    'type' => 'orders',
                    'id'   => (string)$targetOrderId
                ]
            ]
        );

        /** @var OrderDiscount $discount */
        $discount = $this->getEntityManager()->find(OrderDiscount::class, $discountId);
        self::assertEquals($targetOrderId, $discount->getOrder()->getId());
    }
}
