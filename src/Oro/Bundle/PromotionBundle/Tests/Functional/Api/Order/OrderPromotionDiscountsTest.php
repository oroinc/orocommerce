<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Api\Order;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadAppliedPromotionData;

class OrderPromotionDiscountsTest extends RestJsonApiTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadAppliedPromotionData::class,
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            [
                'entity' => 'orders'
            ],
            [
                'filter' => [
                    'id' => ['@simple_order->id'],
                ],
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'attributes' => [
                            'discount' => 20,
                            'shippingDiscount' => 0,
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet()
    {
        $response = $this->get([
            'entity' => 'orders',
            'id' => '<toString(@simple_order->id)>',
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'attributes' => [
                        'discount' => 20,
                        'shippingDiscount' => 0,
                    ]
                ]
            ],
            $response
        );
    }
}
