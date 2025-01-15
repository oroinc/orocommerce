<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Tests\Functional\Api\DataFixtures\LoadProductLatestPurchasesData;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ProductLatestPurchaseTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->markTestSkipped('will be unskipped in BB-24895');

        parent::setUp();
        $this->loadFixtures([
            LoadProductLatestPurchasesData::class
        ]);
    }

    public function testTryToGetListWithoutRequiredFilters(): void
    {
        // @codingStandardsIgnoreStart
        $response = $this->cget(['entity' => 'latestpurchases'], [], [], false);
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'filter constraint',
                    'detail' => 'Either the "customer" or "hierarchicalCustomer" or "customerUser" filter must be provided.',
                ],
                [
                    'title' => 'filter constraint',
                    'detail' => 'The "product" filter is required.',
                ]
            ],
            $response
        );
        // @codingStandardsIgnoreEnd
    }

    public function testTryToGetListWithCustomerAndHierarchicalCustomerFilters(): void
    {
        $response = $this->cget(
            ['entity' => 'latestpurchases'],
            ['filter' => ['customer' => '1', 'hierarchicalCustomer' => '1', 'product' => '@product-1->id']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'The "customer" and "hierarchicalCustomer" filters cannot be used together.',
            ],
            $response
        );
    }

    public function testTryToGetListWithInvalidCustomerFilterValue(): void
    {
        $response = $this->cget(
            ['entity' => 'latestpurchases'],
            ['filter' => ['customer' => 'text', 'website' => '1', 'product' => '@product-1->id']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'Expected integer value. Given "text".',
                'source' => ['parameter' => 'filter[customer]']
            ],
            $response
        );
    }

    public function testTryToGetListWithInvalidProductFilterValue(): void
    {
        $response = $this->cget(
            ['entity' => 'latestpurchases'],
            ['filter' => ['product' => 'text', 'customer' => '@customer.level_1->id', 'website' => '1']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'Expected integer value. Given "text".',
                'source' => ['parameter' => 'filter[product]']
            ],
            $response
        );
    }

    public function testTryToGetListWithInvalidWebsiteFilterValue(): void
    {
        $response = $this->cget(
            ['entity' => 'latestpurchases'],
            ['filter' => ['website' => 'text', 'customer' => '@customer.level_1->id', 'product' => '@product-1->id']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'Expected integer value. Given "text".',
                'source' => ['parameter' => 'filter[website]']
            ],
            $response
        );
    }

    /**
     * @dataProvider productLatestPurchasesDataProvider
     */
    public function testGetList(array $filter, array $expectedResponse): void
    {
        $response = $this->cget(
            ['entity' => 'latestpurchases'],
            ['filter' => $filter]
        );

        $this->assertResponseContains(
            $expectedResponse,
            $response
        );
    }

    public function productLatestPurchasesDataProvider(): array
    {
        // @codingStandardsIgnoreStart
        return [
            'filter by customer 1.1 and product 1' => [
                'filter' => [
                    'customer' => '<toString(@customer.level_1.1->id)>',
                    'product' => [
                        '<toString(@product-1->id)>'
                    ]
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' => '<(implode("-", [@CA->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-1->id, "liter-EUR"]))>',
                            'attributes' => [
                                'price' => '35.0000',
                                'currency' => 'EUR',
                                'purchasedAt' => '2023-12-05T13:23:45Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'liter'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@CA->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'filter by customerUser 1.1 and product 1' => [
                'filter' => [
                    'customerUser' => '<toString(@second_customer.user_at_test.com->id)>',
                    'product' => [
                        '<toString(@product-1->id)>'
                    ]
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' => '<(implode("-", [@CA->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-1->id, "liter-EUR"]))>',
                            'attributes' => [
                                'price' => '35.0000',
                                'currency' => 'EUR',
                                'purchasedAt' => '2023-12-05T13:23:45Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'liter'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@CA->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'filter by customer 1.1 and product 1 and unit "bottle"' => [
                'filter' => [
                    'customer' => '<toString(@customer.level_1.1->id)>',
                    'product' => [
                        '<toString(@product-1->id)>'
                    ],
                    'unit' => ['<toString(@product_unit.bottle)>']
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' =>
                                '<(implode("-", [@US->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-1->id, "bottle-USD"]))>',
                            'attributes' => [
                                'price' => '20.0000',
                                'currency' => 'USD',
                                'purchasedAt' => '2023-12-05T10:20:00Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'bottle'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@US->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'filter by customerUser 1.1 and product 1 and currency "EUR"' => [
                'filter' => [
                    'customerUser' => '<toString(@second_customer.user_at_test.com->id)>',
                    'product' => [
                        '<toString(@product-1->id)>'
                    ],
                    'currency' => ['EUR']
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' => '<(implode("-", [@CA->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-1->id, "liter-EUR"]))>',
                            'attributes' => [
                                'price' => '35.0000',
                                'currency' => 'EUR',
                                'purchasedAt' => '2023-12-05T13:23:45Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'liter'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@CA->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'filter by customer 1.1 and product 1 and website "US"' => [
                'filter' => [
                    'customer' => '<toString(@customer.level_1.1->id)>',
                    'product' => [
                        '<toString(@product-1->id)>'
                    ],
                    'website' => ['<toString(@US->id)>']
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' =>
                                '<(implode("-", [@US->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-1->id, "bottle-USD"]))>',
                            'attributes' => [
                                'price' => '20.0000',
                                'currency' => 'USD',
                                'purchasedAt' => '2023-12-05T10:20:00Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'bottle'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@US->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'filter by customerUser 1.1 and product 1 and website "US"' => [
                'filter' => [
                    'customerUser' => '<toString(@second_customer.user_at_test.com->id)>',
                    'product' => [
                        '<toString(@product-1->id)>'
                    ],
                    'website' => ['<toString(@US->id)>']
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' =>
                                '<(implode("-", [@US->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-1->id, "bottle-USD"]))>',
                            'attributes' => [
                                'price' => '20.0000',
                                'currency' => 'USD',
                                'purchasedAt' => '2023-12-05T10:20:00Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'bottle'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@US->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'filter by customer 1.1 and product 2 and unit ["bottle", "liter"]' => [
                'filter' => [
                    'customer' => '<toString(@customer.level_1.1->id)>',
                    'product' => ['<toString(@product-2->id)>'],
                    'unit' => ['<toString(@product_unit.bottle)>', '<toString(@product_unit.liter)>']
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' =>
                                '<(implode("-", [@US->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-2->id, "bottle-USD"]))>',
                            'attributes' => [
                                'price' => '25.0000',
                                'currency' => 'USD',
                                'purchasedAt' => '2023-12-06T11:34:56Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-2->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'bottle'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@US->id)>'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'latestpurchases',
                            'id' => '<(implode("-", [@CA->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-2->id, "liter-EUR"]))>',
                            'attributes' => [
                                'price' => '33.0000',
                                'currency' => 'EUR',
                                'purchasedAt' => '2023-12-06T10:34:56Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-2->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'liter'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@CA->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'filter by customerUser 1.1 and product 2 and unit ["bottle", "liter"]' => [
                'filter' => [
                    'customerUser' => '<toString(@second_customer.user_at_test.com->id)>',
                    'product' => ['<toString(@product-2->id)>'],
                    'unit' => ['<toString(@product_unit.bottle)>', '<toString(@product_unit.liter)>']
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' =>
                                '<(implode("-", [@US->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-2->id, "bottle-USD"]))>',
                            'attributes' => [
                                'price' => '25.0000',
                                'currency' => 'USD',
                                'purchasedAt' => '2023-12-06T11:34:56Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-2->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'bottle'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@US->id)>'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'latestpurchases',
                            'id' => '<(implode("-", [@CA->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-2->id, "liter-EUR"]))>',
                            'attributes' => [
                                'price' => '33.0000',
                                'currency' => 'EUR',
                                'purchasedAt' => '2023-12-06T10:34:56Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-2->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'liter'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@CA->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'filter by customer 1.1 and product 2 and currency ["USD", "EUR"]' => [
                'filter' => [
                    'customer' => '<toString(@customer.level_1.1->id)>',
                    'product' => ['<toString(@product-2->id)>'],
                    'currency' => ['USD', 'EUR']
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' =>
                                '<(implode("-", [@US->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-2->id, "bottle-USD"]))>',
                            'attributes' => [
                                'price' => '25.0000',
                                'currency' => 'USD',
                                'purchasedAt' => '2023-12-06T11:34:56Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-2->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'bottle'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@US->id)>'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'latestpurchases',
                            'id' => '<(implode("-", [@CA->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-2->id, "liter-EUR"]))>',
                            'attributes' => [
                                'price' => '33.0000',
                                'currency' => 'EUR',
                                'purchasedAt' => '2023-12-06T10:34:56Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-2->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'liter'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@CA->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'filter by customerUser 1.1 and product 2 and currency ["USD", "EUR"]' => [
                'filter' => [
                    'customerUser' => '<toString(@second_customer.user_at_test.com->id)>',
                    'product' => ['<toString(@product-2->id)>'],
                    'currency' => ['USD', 'EUR']
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' =>
                                '<(implode("-", [@US->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-2->id, "bottle-USD"]))>',
                            'attributes' => [
                                'price' => '25.0000',
                                'currency' => 'USD',
                                'purchasedAt' => '2023-12-06T11:34:56Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-2->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'bottle'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@US->id)>'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'latestpurchases',
                            'id' => '<(implode("-", [@CA->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-2->id, "liter-EUR"]))>',
                            'attributes' => [
                                'price' => '33.0000',
                                'currency' => 'EUR',
                                'purchasedAt' => '2023-12-06T10:34:56Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-2->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'liter'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@CA->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'filter by customer 1.1 and product 2 and website ["US", "CA"]' => [
                'filter' => [
                    'customer' => '<toString(@customer.level_1.1->id)>',
                    'product' => ['<toString(@product-2->id)>'],
                    'website' => ['<toString(@US->id)>', '<toString(@CA->id)>']
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' =>
                                '<(implode("-", [@US->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-2->id, "bottle-USD"]))>',
                            'attributes' => [
                                'price' => '25.0000',
                                'currency' => 'USD',
                                'purchasedAt' => '2023-12-06T11:34:56Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-2->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'bottle'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@US->id)>'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'latestpurchases',
                            'id' => '<(implode("-", [@CA->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-2->id, "liter-EUR"]))>',
                            'attributes' => [
                                'price' => '33.0000',
                                'currency' => 'EUR',
                                'purchasedAt' => '2023-12-06T10:34:56Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-2->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'liter'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@CA->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'filter by customer [1.1, 1.2] and product 1' => [
                'filter' => [
                    'customer' => ['<toString(@customer.level_1.1->id)>', '<toString(@customer.level_1.2->id)>'],
                    'product' => [
                        '<toString(@product-1->id)>'
                    ]
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' =>
                                '<(implode("-", [@US->id, @customer.level_1.2->id, @customer.level_1.2_at_test.com->id, @product-1->id, "bottle-USD"]))>',
                            'attributes' => [
                                'price' => '22.0000',
                                'currency' => 'USD',
                                'purchasedAt' => '2023-12-07T16:45:34Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'bottle'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.2->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@US->id)>'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'latestpurchases',
                            'id' => '<(implode("-", [@CA->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-1->id, "liter-EUR"]))>',
                            'attributes' => [
                                'price' => '35.0000',
                                'currency' => 'EUR',
                                'purchasedAt' => '2023-12-05T13:23:45Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'liter'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@CA->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'filter by customerUser [1.1, 1.2] and product 1' => [
                'filter' => [
                    'customerUser' => ['<toString(@customer.level_1.2_at_test.com->id)>', '<toString(@second_customer.user_at_test.com->id)>'],
                    'product' => [
                        '<toString(@product-1->id)>'
                    ]
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' =>
                                '<(implode("-", [@US->id, @customer.level_1.2->id, @customer.level_1.2_at_test.com->id, @product-1->id, "bottle-USD"]))>',
                            'attributes' => [
                                'price' => '22.0000',
                                'currency' => 'USD',
                                'purchasedAt' => '2023-12-07T16:45:34Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'bottle'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.2->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@US->id)>'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'latestpurchases',
                            'id' => '<(implode("-", [@CA->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-1->id, "liter-EUR"]))>',
                            'attributes' => [
                                'price' => '35.0000',
                                'currency' => 'EUR',
                                'purchasedAt' => '2023-12-05T13:23:45Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'liter'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@CA->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'filter by customer [1.1, 1.2] and product 1 and unit "liter"' => [
                'filter' => [
                    'customer' => ['<toString(@customer.level_1.1->id)>', '<toString(@customer.level_1.2->id)>'],
                    'product' => [
                        '<toString(@product-1->id)>'
                    ],
                    'unit' => ['<toString(@product_unit.liter)>']
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' => '<(implode("-", [@CA->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-1->id, "liter-EUR"]))>',
                            'attributes' => [
                                'price' => '35.0000',
                                'currency' => 'EUR',
                                'purchasedAt' => '2023-12-05T13:23:45Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'liter'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@CA->id)>'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'latestpurchases',
                            'id' => '<(implode("-", [@CA->id, @customer.level_1.2->id, @customer.level_1.2_at_test.com->id, @product-1->id, "liter-EUR"]))>',
                            'attributes' => [
                                'price' => '32.0000',
                                'currency' => 'EUR',
                                'purchasedAt' => '2023-12-07T09:45:34Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'liter'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.2->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@CA->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'filter by customerUser [1.1, 1.2] and product 1 and currency "EUR"' => [
                'filter' => [
                    'customerUser' => ['<toString(@customer.level_1.2_at_test.com->id)>', '<toString(@second_customer.user_at_test.com->id)>'],
                    'product' => [
                        '<toString(@product-1->id)>'
                    ],
                    'currency' => ['EUR']
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' => '<(implode("-", [@CA->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-1->id, "liter-EUR"]))>',
                            'attributes' => [
                                'price' => '35.0000',
                                'currency' => 'EUR',
                                'purchasedAt' => '2023-12-05T13:23:45Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'liter'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@CA->id)>'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'latestpurchases',
                            'id' => '<(implode("-", [@CA->id, @customer.level_1.2->id, @customer.level_1.2_at_test.com->id, @product-1->id, "liter-EUR"]))>',
                            'attributes' => [
                                'price' => '32.0000',
                                'currency' => 'EUR',
                                'purchasedAt' => '2023-12-07T09:45:34Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'liter'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.2->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@CA->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'filter by customer [1.1, 1.2] and product 1 and website "CA"' => [
                'filter' => [
                    'customer' => ['<toString(@customer.level_1.1->id)>', '<toString(@customer.level_1.2->id)>'],
                    'product' => [
                        '<toString(@product-1->id)>'
                    ],
                    'website' => '<toString(@CA->id)>'
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' => '<(implode("-", [@CA->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-1->id, "liter-EUR"]))>',
                            'attributes' => [
                                'price' => '35.0000',
                                'currency' => 'EUR',
                                'purchasedAt' => '2023-12-05T13:23:45Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'liter'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@CA->id)>'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'latestpurchases',
                            'id' => '<(implode("-", [@CA->id, @customer.level_1.2->id, @customer.level_1.2_at_test.com->id, @product-1->id, "liter-EUR"]))>',
                            'attributes' => [
                                'price' => '32.0000',
                                'currency' => 'EUR',
                                'purchasedAt' => '2023-12-07T09:45:34Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'liter'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.2->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@CA->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'filter by customer [1.1, 1.2] and product [1, 2]' => [
                'filter' => [
                    'customer' => ['<toString(@customer.level_1.1->id)>', '<toString(@customer.level_1.2->id)>'],
                    'product' => ['<toString(@product-1->id)>', '<toString(@product-2->id)>']
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' =>
                                '<(implode("-", [@US->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-2->id, "bottle-USD"]))>',
                            'attributes' => [
                                'price' => '25.0000',
                                'currency' => 'USD',
                                'purchasedAt' => '2023-12-06T11:34:56Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-2->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'bottle'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@US->id)>'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'latestpurchases',
                            'id' =>
                                '<(implode("-", [@US->id, @customer.level_1.2->id, @customer.level_1.2_at_test.com->id, @product-1->id, "bottle-USD"]))>',
                            'attributes' => [
                                'price' => '22.0000',
                                'currency' => 'USD',
                                'purchasedAt' => '2023-12-07T16:45:34Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'bottle'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.2->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@US->id)>'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'latestpurchases',
                            'id' =>
                                '<(implode("-", [@US->id, @customer.level_1.2->id, @customer.level_1.2_at_test.com->id, @product-2->id, "bottle-USD"]))>',
                            'attributes' => [
                                'price' => '24.0000',
                                'currency' => 'USD',
                                'purchasedAt' => '2023-12-08T14:23:45Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-2->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'bottle'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.2->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@US->id)>'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'latestpurchases',
                            'id' => '<(implode("-", [@CA->id, @customer.level_1.1->id, @second_customer.user_at_test.com->id, @product-1->id, "liter-EUR"]))>',
                            'attributes' => [
                                'price' => '35.0000',
                                'currency' => 'EUR',
                                'purchasedAt' => '2023-12-05T13:23:45Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'liter'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.1->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@CA->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'filter by customer 1 and product [1, 2]' => [
                'filter' => [
                    'customer' => '<toString(@customer.level_1->id)>',
                    'product' => ['<toString(@product-1->id)>', '<toString(@product-2->id)>']
                ],
                'expectedResponse' => [
                    'data' => [
                    ]
                ]
            ],
            'filter by hierarchicalCustomer 1 and product [1, 2]' => [
                'filter' => [
                    'hierarchicalCustomer' => '<toString(@customer.level_1->id)>',
                    'product' => ['<toString(@product-1->id)>', '<toString(@product-2->id)>']
                ],
                'expectedResponse' => [
                    'data' => [
                        [
                            'type' => 'latestpurchases',
                            'id' =>
                                '<(implode("-", [@US->id, @customer.level_1.2->id, @customer.level_1.2_at_test.com->id, @product-1->id, "bottle-USD"]))>',
                            'attributes' => [
                                'price' => '22.0000',
                                'currency' => 'USD',
                                'purchasedAt' => '2023-12-07T16:45:34Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-1->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'bottle'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.2->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@US->id)>'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'latestpurchases',
                            'id' =>
                                '<(implode("-", [@US->id, @customer.level_1.2->id, @customer.level_1.2_at_test.com->id, @product-2->id, "bottle-USD"]))>',
                            'attributes' => [
                                'price' => '24.0000',
                                'currency' => 'USD',
                                'purchasedAt' => '2023-12-08T14:23:45Z'
                            ],
                            'relationships' => [
                                'product' => [
                                    'data' => [
                                        'type' => 'products',
                                        'id' => '<toString(@product-2->id)>'
                                    ]
                                ],
                                'unit' => [
                                    'data' => [
                                        'type' => 'productunits',
                                        'id' => 'bottle'
                                    ]
                                ],
                                'customer' => [
                                    'data' => [
                                        'type' => 'customers',
                                        'id' => '<toString(@customer.level_1.2->id)>'
                                    ]
                                ],
                                'website' => [
                                    'data' => [
                                        'type' => 'websites',
                                        'id' => '<toString(@US->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function testTryToGet(): void
    {
        $response = $this->get(
            [
                'entity' => 'latestpurchases',
                'id' => '<(implode("-", [@US->id, @customer.level_1->id, @product-1->id, "USD-bottle"]))>'
            ],
            [],
            [],
            false
        );
        static::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'latestpurchases'],
            [],
            [],
            false
        );
        static::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            [
                'entity' => 'latestpurchases',
                'id' => '<(implode("-", [@US->id, @customer.level_1->id, @product-1->id, "USD-bottle"]))>'
            ],
            [],
            [],
            false
        );
        static::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            [
                'entity' => 'latestpurchases',
                'id' => '<(implode("-", [@US->id, @customer.level_1->id, @product-1->id, "USD-bottle"]))>'
            ],
            [],
            [],
            false
        );
        static::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'latestpurchases'],
            [
                'filter' => [
                    'id' => '<(implode("-", [@US->id, @customer.level_1->id, @product-1->id, "USD-bottle"]))>'
                ]
            ],
            [],
            false
        );
        static::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
