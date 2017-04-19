<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderDiscounts;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Symfony\Component\HttpFoundation\Response;

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
        $response = $this->cget(['entity' => $this->getEntityType(OrderDiscount::class)]);
        $this->assertResponseContains(__DIR__.'/responses/discount/get_discounts.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get([
            'entity' => $this->getEntityType(OrderDiscount::class),
            'id' => '<toString(@orderDiscount.percent->id)>',
        ]);
        $this->assertResponseContains(__DIR__.'/responses/discount/get_discount.yml', $response);
    }

    public function testGetOrderSubResource()
    {
        /** @var OrderDiscount $discount */
        $discount = $this->getReference(LoadOrderDiscounts::REFERENCE_DISCOUNT_AMOUNT);

        $uri = $this->getUrl(
            'oro_rest_api_get_subresource',
            [
                'entity' => $this->getEntityType(OrderDiscount::class),
                'id' => $discount->getId(),
                'association' => 'order',
            ]
        );
        $response = $this->request('GET', $uri);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $order = json_decode($response->getContent(), true)['data'];

        static::assertEquals($this->getReferenceOrder()->getId(), $order['id']);
    }

    public function testGetOrderRelationship()
    {
        /** @var OrderDiscount $discount */
        $discount = $this->getReference(LoadOrderDiscounts::REFERENCE_DISCOUNT_AMOUNT);

        $uri = $this->getUrl(
            'oro_rest_api_get_relationship',
            [
                'entity' => $this->getEntityType(OrderDiscount::class),
                'id' => $discount->getId(),
                'association' => 'order',
            ]
        );
        $response = $this->request('GET', $uri);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $expected = [
            'data' => [
                'type' => $this->getEntityType(Order::class),
                'id' => (string)$this->getReferenceOrder()->getId(),
            ],
        ];

        static::assertEquals($expected, $content);
    }

    public function testCreate()
    {
        $description = 'New Discount';
        $percent = 0.201;
        $amount = 180;

        $this->post(
            ['entity' => $this->getEntityType(OrderDiscount::class)],
            __DIR__.'/responses/discount/create_discount.yml'
        );

        /** @var OrderDiscount $discount */
        $discount = $this->getManager()
            ->getRepository(OrderDiscount::class)
            ->findOneBy(['description' => $description]);

        /** @var Order $order */
        $order = $this->getManager()
            ->getRepository(Order::class)
            ->find($this->getReferenceOrder()->getId());

        static::assertSame($description, $discount->getDescription());
        static::assertEquals($percent, $discount->getPercent());
        static::assertEquals($amount, $discount->getAmount());
        static::assertSame(OrderDiscount::TYPE_AMOUNT, $discount->getType());

        $discountAmount = $discount->getPercent() * $order->getSubtotal() + $discount->getAmount();

        static::assertSame($discountAmount, $order->getTotalDiscounts()->getValue());

        $order->removeDiscount($discount);
        $this->getManager()->remove($discount);
        $this->getManager()->flush();
        $this->getManager()->clear();
    }

    public function testUpdateDescription()
    {
        $newDescription = 'new description';

        /** @var OrderDiscount $discount */
        $discount = $this->getReference(LoadOrderDiscounts::REFERENCE_DISCOUNT_AMOUNT);

        $requestData = [
            'data' => [
                'type' => $this->getEntityType(OrderDiscount::class),
                'id' => (string)$discount->getId(),
                'attributes' => [
                    'description' => $newDescription,
                ],
            ],
        ];

        $uri = $this->getUrl(
            'oro_rest_api_patch',
            [
                'entity' => $this->getEntityType(OrderDiscount::class),
                'id' => $discount->getId(),
            ]
        );
        $response = $this->request('PATCH', $uri, $requestData);

        /** @var OrderDiscount $updatedDiscount */
        $updatedDiscount = $this->getManager()
            ->getRepository(OrderDiscount::class)
            ->find($discount->getId());

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame($newDescription, $updatedDiscount->getDescription());
    }

    public function testUpdateAmount()
    {
        $newAmount = 300;

        /** @var OrderDiscount $discount */
        $discount = $this->getReference(LoadOrderDiscounts::REFERENCE_DISCOUNT_AMOUNT);

        $requestData = [
            'data' => [
                'type' => $this->getEntityType(OrderDiscount::class),
                'id' => (string)$discount->getId(),
                'attributes' => [
                    'amount' => $newAmount,
                ],
            ],
        ];

        $uri = $this->getUrl(
            'oro_rest_api_patch',
            [
                'entity' => $this->getEntityType(OrderDiscount::class),
                'id' => $discount->getId(),
            ]
        );
        $this->request('PATCH', $uri, $requestData);

        /** @var OrderDiscount $updatedDiscount */
        $updatedDiscount = $this->getManager()
            ->getRepository(OrderDiscount::class)
            ->find($discount->getId());

        static::assertEquals($newAmount, $updatedDiscount->getOrder()->getTotalDiscounts()->getValue());
    }

    public function testPatchOrderRelationship()
    {
        /** @var OrderDiscount $discount */
        $discount = $this->getReference(LoadOrderDiscounts::REFERENCE_DISCOUNT_AMOUNT);

        /** @var Order $order2 */
        $order2 = $this->getReference(LoadOrders::MY_ORDER);

        $uri = $this->getUrl(
            'oro_rest_api_patch_relationship',
            [
                'entity' => $this->getEntityType(OrderDiscount::class),
                'id' => (string)$discount->getId(),
                'association' => 'order',
            ]
        );
        $data = [
            'data' => [
                'type' => $this->getEntityType(Order::class),
                'id' => (string)$order2->getId(),
            ],
        ];

        $response = $this->request('PATCH', $uri, $data);

        /** @var OrderDiscount $updatedDiscount */
        $updatedDiscount = $this->getManager()
            ->getRepository(OrderDiscount::class)
            ->find($discount->getId());

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        static::assertEquals($order2->getId(), $updatedDiscount->getOrder()->getId());
    }

    public function testDeleteByFilter()
    {
        /** @var OrderDiscount $discount */
        $discount = $this->getReference(LoadOrderDiscounts::REFERENCE_DISCOUNT_AMOUNT);
        $discountId = $discount->getId();

        $uri = $this->getUrl(
            'oro_rest_api_cget',
            ['entity' => $this->getEntityType(OrderDiscount::class)]
        );
        $response = $this->request(
            'DELETE',
            $uri,
            ['filter' => ['id' => $discountId]]
        );

        $this->getManager()->clear();

        $removedDiscount = $this->getManager()
            ->getRepository(OrderDiscount::class)
            ->find($discountId);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);
        static::assertNull($removedDiscount);
    }

    /**
     * @return ObjectManager
     */
    private function getManager()
    {
        return static::getContainer()->get('doctrine')->getManager();
    }

    /**
     * @return Order
     */
    private function getReferenceOrder()
    {
        return $this->getReference(LoadOrders::ORDER_1);
    }
}
