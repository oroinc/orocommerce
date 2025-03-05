<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendSubresources\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdditionalCouponData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutCouponsTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadCheckoutData::class,
            LoadAdditionalCouponData::class
        ]);
    }

    public function testGetOnlyCoupons(): void
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
                        'totalValue' => '273.8500',
                        'totals' => [
                            ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '331.9900'],
                            ['subtotalType' => 'discount', 'description' => 'Discount', 'amount' => '-58.1400']
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
                        'totalValue' => '321.9900',
                        'totals' => [
                            ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '331.9900'],
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
