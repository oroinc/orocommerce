<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Api\RestJsonApi;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\PromotionBundle\Tests\Functional\Api\DataFixtures\LoadDisablePromotionsOrders;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadAppliedPromotionData;

class OrderPromotionDiscountsTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAppliedPromotionData::class,
            LoadDisablePromotionsOrders::class
        ]);
    }

    /**
     * @return bool
     */
    private function isPostgreSql()
    {
        return $this->getEntityManager()->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform;
    }

    public function testGetList()
    {
        $order1Id = $this->getReference('simple_order')->getId();
        $order2Id = $this->getReference('simple_order2')->getId();
        $withoutPromotionOrder1Id = $this->getReference('disabled_promotions_order1')->getId();
        $withoutPromotionOrder2Id = $this->getReference('disabled_promotions_order2')->getId();

        $response = $this->cget(
            ['entity' => 'orders'],
            ['filter[id]' => implode(',', [$order1Id, $order2Id, $withoutPromotionOrder1Id, $withoutPromotionOrder2Id])]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => (string)$order1Id,
                        'attributes' => [
                            'subtotalValue'     => '789.0000',
                            'totalValue'        => '1234.0000',
                            'discount'          => 20,
                            'shippingDiscount'  => 1.99,
                            'disablePromotions' => $this->isPostgreSql() ? false : null
                        ]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => (string)$order2Id,
                        'attributes' => [
                            'subtotalValue'     => '789.0000',
                            'totalValue'        => '1234.0000',
                            'discount'          => 0,
                            'shippingDiscount'  => 0,
                            'disablePromotions' => $this->isPostgreSql() ? false : null
                        ]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => (string)$withoutPromotionOrder1Id,
                        'attributes' => [
                            'subtotalValue'     => '789.0000',
                            'totalValue'        => '1234.0000',
                            'discount'          => null,
                            'shippingDiscount'  => null,
                            'disablePromotions' => true
                        ]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => (string)$withoutPromotionOrder2Id,
                        'attributes' => [
                            'subtotalValue'     => '789.0000',
                            'totalValue'        => '1234.0000',
                            'discount'          => null,
                            'shippingDiscount'  => null,
                            'disablePromotions' => true
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
                        'discount'          => 20,
                        'shippingDiscount'  => 1.99,
                        'disablePromotions' => $this->isPostgreSql() ? false : null
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetOrderWithDisabledPromotions()
    {
        $orderId = $this->getReference('disabled_promotions_order1')->getId();

        $response = $this->get(
            ['entity' => 'orders', 'id' => (string)$orderId]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => (string)$orderId,
                    'attributes' => [
                        'discount'          => null,
                        'shippingDiscount'  => null,
                        'disablePromotions' => true
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

    public function testDisablePromotionsForOrder()
    {
        $orderId = $this->getReference('simple_order')->getId();
        $response = $this->patch(
            ['entity' => 'orders', 'id' => $orderId],
            [
                'data' => [
                    'type'          => 'orders',
                    'id'            => (string)$orderId,
                    'attributes'    => [
                        'disablePromotions' => true
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => (string)$orderId,
                    'attributes' => [
                        'subtotalValue'     => '444.5000',
                        'totalValue'        => '444.5000',
                        'discount'          => null,
                        'shippingDiscount'  => null,
                        'disablePromotions' => true
                    ]
                ]
            ],
            $response
        );
    }
}
