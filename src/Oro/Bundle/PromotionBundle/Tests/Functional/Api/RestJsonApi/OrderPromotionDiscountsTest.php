<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadAppliedPromotionData;

class OrderPromotionDiscountsTest extends RestJsonApiTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAppliedPromotionData::class
        ]);
    }

    public function testGetList()
    {
        $order1Id = $this->getReference('simple_order')->getId();
        $order2Id = $this->getReference('simple_order2')->getId();

        $response = $this->cget(
            ['entity' => 'orders'],
            ['filter[id]' => implode(',', [$order1Id, $order2Id])]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => (string)$order1Id,
                        'attributes' => [
                            'discount'         => 20,
                            'shippingDiscount' => 1.99
                        ]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => (string)$order2Id,
                        'attributes' => [
                            'discount'         => 0,
                            'shippingDiscount' => 0
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet()
    {
        $orderId = $this->getReference('simple_order')->getId();

        $response = $this->get(
            ['entity' => 'orders', 'id' => (string)$orderId]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => (string)$orderId,
                    'attributes' => [
                        'discount'         => 20,
                        'shippingDiscount' => 1.99
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetShouldReturnCorrectDiscountEvenIfOtherFieldsWereNotRequested()
    {
        $orderId = $this->getReference('simple_order')->getId();

        $response = $this->get(
            ['entity' => 'orders', 'id' => (string)$orderId],
            ['fields[orders]' => 'discount']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => (string)$orderId,
                    'attributes' => [
                        'discount' => 20
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContent['data']['attributes']);
    }

    public function testGetShouldReturnCorrectShippingDiscountEvenIfOtherFieldsWereNotRequested()
    {
        $orderId = $this->getReference('simple_order')->getId();

        $response = $this->get(
            ['entity' => 'orders', 'id' => (string)$orderId],
            ['fields[orders]' => 'shippingDiscount']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => (string)$orderId,
                    'attributes' => [
                        'shippingDiscount' => 1.99
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContent['data']['attributes']);
    }
}
