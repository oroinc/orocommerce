<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCompetedCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\PromotionBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdditionalCouponData;
use Oro\Bundle\PromotionBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutCouponData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutCouponsAndDiscountsTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadCheckoutData::class,
            LoadCompetedCheckoutData::class,
            LoadCheckoutCouponData::class,
            LoadAdditionalCouponData::class
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'checkouts']);
        $this->assertResponseContains('cget_checkout.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>']);
        $this->assertResponseContains('get_checkout.yml', $response);
    }

    public function testGetWithTotalValueAndTotalsOnly(): void
    {
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>'],
            ['fields[checkouts]' => 'totalValue,totals']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => '<toString(@checkout.completed->id)>',
                    'attributes' => [
                        'totalValue' => '90.4500',
                        'totals' => [
                            ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '100.5000'],
                            ['subtotalType' => 'discount', 'description' => 'Discount', 'amount' => '-20.0500'],
                            ['subtotalType' => 'shipping_cost', 'description' => 'Shipping', 'amount' => '10.0000']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetTotalValueOnly(): void
    {
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>'],
            ['fields[checkouts]' => 'totalValue']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => '<toString(@checkout.completed->id)>',
                    'attributes' => [
                        'totalValue' => '90.4500'
                    ]
                ]
            ],
            $response
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseData['data']['attributes']);
        self::assertArrayNotHasKey('relationships', $responseData['data']);
    }

    public function testGetTotalsOnly(): void
    {
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>'],
            ['fields[checkouts]' => 'totals']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => '<toString(@checkout.completed->id)>',
                    'attributes' => [
                        'totals' => [
                            ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '100.5000'],
                            ['subtotalType' => 'discount', 'description' => 'Discount', 'amount' => '-20.0500'],
                            ['subtotalType' => 'shipping_cost', 'description' => 'Shipping', 'amount' => '10.0000']
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseData['data']['attributes']);
        self::assertArrayNotHasKey('relationships', $responseData['data']);
    }

    public function testGetWithCouponsOnly(): void
    {
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>'],
            ['fields[checkouts]' => 'coupons']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => '<toString(@checkout.in_progress->id)>',
                    'attributes' => [
                        'coupons' => [
                            [
                                'couponCode' => 'coupon_with_promo_and_valid_from_and_until',
                                'description' => 'Order percent promotion name'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseData['data']['attributes']);
        self::assertArrayNotHasKey('relationships', $responseData['data']);
    }

    public function testGetCompletedWithCouponsOnly(): void
    {
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>'],
            ['fields[checkouts]' => 'coupons']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => '<toString(@checkout.completed->id)>',
                    'attributes' => [
                        'coupons' => [
                            [
                                'couponCode' => 'coupon_with_promo_and_valid_from_and_until',
                                'description' => 'Order percent promotion name'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetLineItemWithIncludeCheckoutCoupons(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutlineitems', 'id' => '<toString(@checkout.completed.line_item.1->id)>'],
            ['include' => 'checkout', 'fields[checkouts]' => 'coupons']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => '<toString(@checkout.completed.line_item.1->id)>',
                    'relationships' => [
                        'checkout' => [
                            'data' => ['type' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'checkouts',
                        'id' => '<toString(@checkout.completed->id)>',
                        'attributes' => [
                            'coupons' => [
                                [
                                    'couponCode' => 'coupon_with_promo_and_valid_from_and_until',
                                    'description' => 'Order percent promotion name'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreateLineItemWithRequiredDataOnly(): void
    {
        $data = $this->getRequestData('create_checkout_line_item_min.yml');
        $response = $this->post(
            ['entity' => 'checkoutlineitems'],
            array_merge(['filters' => 'include=checkout&fields[checkouts]=totalValue,totals'], $data)
        );

        $lineItemId = $this->getResourceId($response);
        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(CheckoutLineItem::class, $lineItemId);
        $expectedData = $data;
        $expectedData['data']['id'] = $lineItemId;
        $expectedData['data']['attributes']['priceFixed'] = false;
        $expectedData['data']['attributes']['price'] = '100.5000';
        $expectedData['data']['attributes']['currency'] = 'USD';
        $expectedData['data']['attributes']['shippingMethod'] = null;
        $expectedData['data']['attributes']['shippingMethodType'] = null;
        $expectedData['data']['attributes']['shippingEstimateAmount'] = null;
        $expectedData['data']['relationships']['group']['data'] = null;
        $expectedData['included'] = [
            [
                'type' => 'checkouts',
                'id' => '<toString(@checkout.open->id)>',
                'attributes' => [
                    'totalValue' => '111.4000',
                    'totals' => [
                        ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '121.4000'],
                        ['subtotalType' => 'discount', 'description' => 'Discount', 'amount' => '-10.0000']
                    ]
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);
    }

    public function testUpdateLineItem(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $data = [
            'data' => [
                'type' => 'checkoutlineitems',
                'id' => (string)$lineItemId,
                'attributes' => [
                    'quantity' => 5
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            array_merge(['filters' => 'include=checkout&fields[checkouts]=totalValue,totals'], $data)
        );

        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(CheckoutLineItem::class, $lineItemId);
        $expectedData = $data;
        $expectedData['data']['attributes']['price'] = '100.5000';
        $expectedData['data']['attributes']['currency'] = 'USD';
        $expectedData['included'][] = [
            'type' => 'checkouts',
            'id' => '<toString(@checkout.in_progress->id)>',
            'attributes' => [
                'totalValue' => '650.5900',
                'totals' => [
                    ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '733.9900'],
                    ['subtotalType' => 'discount', 'description' => 'Discount', 'amount' => '-83.4000']
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);
        self::assertSame(5.0, $lineItem->getQuantity());
    }

    public function testUpdateLineItemPrice(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $data = [
            'data' => [
                'type' => 'checkoutlineitems',
                'id' => (string)$lineItemId,
                'attributes' => [
                    'price' => 150.1,
                    'currency' => 'USD',
                    'priceFixed' => true
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            array_merge(['filters' => 'include=checkout&fields[checkouts]=totalValue,totals'], $data)
        );
        $expectedData = $data;
        $expectedData['data']['attributes']['price'] = '100.5000';
        $expectedData['included'][] = [
            'type' => 'checkouts',
            'id' => '<toString(@checkout.in_progress->id)>',
            'attributes' => [
                'totalValue' => '198.3400',
                'totals' => [
                    ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '231.4900'],
                    ['subtotalType' => 'discount', 'description' => 'Discount', 'amount' => '-33.1500']
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);

        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(CheckoutLineItem::class, $lineItemId);
        self::assertNull($lineItem->getValue());
        self::assertNull($lineItem->getCurrency());
    }

    public function testCreateProductKitItemLineItemWithRequiredDataOnly(): void
    {
        $data = $this->getRequestData('create_checkout_kit_item_line_item_min.yml');
        $response = $this->post(
            ['entity' => 'checkoutproductkititemlineitems'],
            array_merge(['filters' => 'include=lineItem.checkout&fields[checkouts]=totalValue,totals'], $data)
        );
        $expectedData = $data;
        $expectedData['included'] = [
            [
                'type' => 'checkouts',
                'id' => '<toString(@checkout.in_progress->id)>',
                'attributes' => [
                    'totalValue' => '1114.1700',
                    'totals' => [
                        ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '1249.0800'],
                        ['subtotalType' => 'discount', 'description' => 'Discount', 'amount' => '-134.9100']
                    ]
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);
    }

    public function testUpdateProductKitItemLineItem(): void
    {
        $kitItemId = $this->getReference('checkout.in_progress.line_item.2.kit_item.1')->getId();
        $data = [
            'data' => [
                'type' => 'checkoutproductkititemlineitems',
                'id' => (string)$kitItemId,
                'attributes' => [
                    'quantity' => 2
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'checkoutproductkititemlineitems', 'id' => (string)$kitItemId],
            array_merge(['filters' => 'include=lineItem.checkout&fields[checkouts]=totalValue,totals'], $data)
        );

        /** @var CheckoutProductKitItemLineItem $kitItem */
        $kitItem = $this->getEntityManager()->find(CheckoutProductKitItemLineItem::class, $kitItemId);
        $expectedData = $data;
        $expectedData['included'][] = [
            'type' => 'checkouts',
            'id' => '<toString(@checkout.in_progress->id)>',
            'attributes' => [
                'totalValue' => '1207.1500',
                'totals' => [
                    ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '1352.3900'],
                    ['subtotalType' => 'discount', 'description' => 'Discount', 'amount' => '-145.2400']
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);
        self::assertSame(2.0, $kitItem->getQuantity());
    }

    public function testTryToUpdateCoupons(): void
    {
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>'],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => '<toString(@checkout.in_progress->id)>',
                    'attributes' => [
                        'coupons' => [
                            [
                                'couponCode' => 'coupon_with_promo_and_valid_from_and_until',
                                'description' => 'Order percent promotion name'
                            ],
                            [
                                'couponCode' => 'additional_coupon_with_promo',
                                'description' => 'Order percent additional promotion name'
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => '<toString(@checkout.in_progress->id)>',
                    'attributes' => [
                        'coupons' => [
                            [
                                'couponCode' => 'coupon_with_promo_and_valid_from_and_until',
                                'description' => 'Order percent promotion name'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithCoupons(): void
    {
        $response = $this->post(
            ['entity' => 'checkouts'],
            [
                'data' => [
                    'type' => 'checkouts',
                    'attributes' => [
                        'coupons' => [
                            [
                                'couponCode' => 'coupon_with_promo_and_valid_from_and_until',
                                'description' => 'Order percent promotion name'
                            ]
                        ]
                    ]
                ]
            ]
        );

        $checkoutId = (int)$this->getResourceId($response);
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertNotNull($checkout);

        $responseContent = self::jsonToArray($response->getContent());
        self::assertCount(0, $responseContent['data']['attributes']['coupons']);
    }

    public function testApplyCoupon(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>', 'association' => 'coupons'],
            ['meta' => ['couponCode' => LoadAdditionalCouponData::ADDITIONAL_COUPON_WITH_PROMO]]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => '<toString(@checkout.in_progress->id)>',
                    'attributes' => [
                        'poNumber' => null,
                        'customerNotes' => 'checkout.in_progress',
                        'totalValue' => '1047.2000',
                        'totals' => [
                            ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '1236.4900'],
                            ['subtotalType' => 'discount', 'description' => 'Discount', 'amount' => '-189.2900']
                        ],
                        'coupons' => [
                            [
                                'couponCode' => 'coupon_with_promo_and_valid_from_and_until',
                                'description' => 'Order percent promotion name'
                            ],
                            [
                                'couponCode' => 'additional_coupon_with_promo',
                                'description' => 'Order percent additional promotion name'
                            ]
                        ]
                    ],
                    'relationships' => [
                        'source' => [
                            'data' => [
                                'type' => 'shoppinglists',
                                'id' => '<toString(@checkout.in_progress.shopping_list->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToApplyCouponWhenUnknownCouponCode(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>', 'association' => 'coupons'],
            ['meta' => ['couponCode' => 'wrong-code']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'coupon constraint',
                'detail' => 'Invalid coupon code, please try another'
            ],
            $response
        );
    }

    public function testTryToApplyCouponWhenItAlreadyApplied(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>', 'association' => 'coupons'],
            ['meta' => ['couponCode' => LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL]],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'coupon constraint',
                'detail' => 'This coupon has been already added'
            ],
            $response
        );
    }

    public function testTryToApplyCouponWhenIsNotApplicable(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>', 'association' => 'coupons'],
            ['meta' => ['couponCode' => LoadCouponData::COUPON_WITH_PROMO_AND_EXPIRED]],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'coupon constraint',
                'detail' => 'Coupon is expired'
            ],
            $response
        );
    }

    public function testRemoveCoupon(): void
    {
        $response = $this->deleteSubresource(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>', 'association' => 'coupons'],
            ['meta' => ['couponCode' => LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL]]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => '<toString(@checkout.in_progress->id)>',
                    'attributes' => [
                        'poNumber' => null,
                        'customerNotes' => 'checkout.in_progress',
                        'totalValue' => '1226.4900',
                        'totals' => [
                            ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '1236.4900'],
                            ['subtotalType' => 'discount', 'description' => 'Discount', 'amount' => '-10.0000']
                        ],
                        'coupons' => []
                    ],
                    'relationships' => [
                        'source' => [
                            'data' => [
                                'type' => 'shoppinglists',
                                'id' => '<toString(@checkout.in_progress.shopping_list->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToRemoveCouponWhenUnknownCouponCode(): void
    {
        $response = $this->deleteSubresource(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>', 'association' => 'coupons'],
            ['meta' => ['couponCode' => 'wrong-code']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'coupon constraint',
                'detail' => 'The coupon hasn\'t been applied, so it cannot be deleted.'
            ],
            $response
        );
    }

    public function testTryToRemoveCouponWhenItIsNotApplied(): void
    {
        $response = $this->deleteSubresource(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>', 'association' => 'coupons'],
            ['meta' => ['couponCode' => LoadAdditionalCouponData::ADDITIONAL_COUPON_WITH_PROMO]],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'coupon constraint',
                'detail' => 'The coupon hasn\'t been applied, so it cannot be deleted.'
            ],
            $response
        );
    }

    public function testTryToGetSubresourceForCoupons(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>', 'association' => 'coupons'],
            [],
            [],
            false
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, POST, DELETE');
    }

    public function testTryToUpdateSubresourceForCoupons(): void
    {
        $response = $this->patchSubresource(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>', 'association' => 'coupons'],
            [],
            [],
            false
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, POST, DELETE');
    }
}
