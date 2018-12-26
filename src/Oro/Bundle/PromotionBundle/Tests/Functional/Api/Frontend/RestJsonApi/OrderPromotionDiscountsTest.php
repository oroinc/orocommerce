<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Brick\Math\BigDecimal;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\PromotionBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadOrderPromotionalDiscountsData;
use Oro\DBAL\Types\MoneyType;

class OrderPromotionDiscountsTest extends FrontendRestJsonApiTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadOrderPromotionalDiscountsData::class
        ]);
    }

    /**
     * @param mixed $value
     *
     * @return string|null
     */
    private function getMoneyValue($value)
    {
        if (null !== $value) {
            $value = (string)BigDecimal::of($value)->toScale(MoneyType::TYPE_SCALE);
        }

        return $value;
    }

    /**
     * @return array
     */
    private function getOrder1Discounts(): array
    {
        return [
            [
                'type'        => 'order',
                'description' => 'Percent Discount',
                'amount'      => $this->getMoneyValue(158.589)
            ],
            [
                'type'        => 'order',
                'description' => 'Amount Discount',
                'amount'      => $this->getMoneyValue(158.589)
            ],
            [
                'type'        => 'promotion.order',
                'description' => 'Discount',
                'amount'      => $this->getMoneyValue(1.2)
            ],
            [
                'type'        => 'promotion.shipping',
                'description' => 'Shipping Discount',
                'amount'      => $this->getMoneyValue(0.3)
            ]
        ];
    }

    public function testGetListShouldReturnPromotionDiscounts()
    {
        $orderDiscounts = $this->getOrder1Discounts();

        $response = $this->cget(
            ['entity' => 'orders'],
            ['filter[id]' => '<toString(@order1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@order1->id)>',
                        'attributes' => [
                            'discounts' => $orderDiscounts
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertCount(count($orderDiscounts), $responseContent['data'][0]['attributes']['discounts']);
    }

    public function testGetShouldReturnPromotionDiscounts()
    {
        $orderDiscounts = $this->getOrder1Discounts();

        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => '<toString(@order1->id)>',
                    'attributes' => [
                        'discounts' => $orderDiscounts
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertCount(count($orderDiscounts), $responseContent['data']['attributes']['discounts']);
    }

    public function testGetForOrderWithoutDiscounts()
    {
        $orderDiscounts = [];

        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@order2->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => '<toString(@order2->id)>',
                    'attributes' => [
                        'discounts' => $orderDiscounts
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertCount(count($orderDiscounts), $responseContent['data']['attributes']['discounts']);
    }
}
