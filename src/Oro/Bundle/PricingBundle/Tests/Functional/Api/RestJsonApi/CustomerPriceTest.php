<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CustomerPriceTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadCombinedProductPrices::class]);
    }

    public function testTryToGetListWithoutRequiredFilters(): void
    {
        $response = $this->cget(['entity' => 'customerprices'], [], [], false);

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'filter constraint',
                    'detail' => 'The "customer" filter is required.'
                ],
                [
                    'title' => 'filter constraint',
                    'detail' => 'The "product" filter is required.'
                ],
                [
                    'title' => 'filter constraint',
                    'detail' => 'The "website" filter is required.'
                ]
            ],
            $response
        );
    }

    public function testTryToGetListWithInvalidCustomerFilterValue(): void
    {
        $response = $this->cget(
            ['entity' => 'customerprices'],
            ['filter' => ['customer' => 'text', 'website' => '@US->id', 'product' => '@product-1->id']],
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
            ['entity' => 'customerprices'],
            ['filter' => ['product' => 'text', 'customer' => '@customer.level_1->id', 'website' => '@US->id']],
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
            ['entity' => 'customerprices'],
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

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'customerprices'],
            [
                'filter' => [
                    'customer' => '@customer.level_1->id',
                    'website' => '@US->id',
                    'product' => '@product-1->id'
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customerprices',
                        'id' => '<(implode("-", [@customer.level_1->id, @US->id, @product-1->id, "USD-bottle-1"]))>',
                        'attributes' => [
                            'currency' => 'USD',
                            'quantity' => 1,
                            'value' => '1.1000'
                        ],
                        'relationships' => [
                            'unit' => [
                                'data' => ['type' => 'productunits', 'id' => 'bottle']
                            ],
                            'product' => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                            ],
                            'customer' => [
                                'data' => ['type' => 'customers', 'id' => '<toString(@customer.level_1->id)>']
                            ],
                            'website' => [
                                'data' => ['type' => 'websites', 'id' => '<toString(@US->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => 'customerprices',
                        'id' => '<(implode("-", [@customer.level_1->id, @US->id, @product-1->id, "USD-liter-10"]))>',
                        'attributes' => [
                            'currency' => 'USD',
                            'quantity' => 10,
                            'value' => '1.2000'
                        ],
                        'relationships' => [
                            'unit' => [
                                'data' => ['type' => 'productunits', 'id' => 'liter']
                            ],
                            'product' => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                            ],
                            'customer' => [
                                'data' => ['type' => 'customers', 'id' => '<toString(@customer.level_1->id)>']
                            ],
                            'website' => [
                                'data' => ['type' => 'websites', 'id' => '<toString(@US->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithAllFilters(): void
    {
        $response = $this->cget(
            ['entity' => 'customerprices'],
            [
                'filter' => [
                    'customer' => '@customer.level_1->id',
                    'website' => '@US->id',
                    'product' => ['@product-1->id', '@product-2->id', '@product-3->id', '@product-4->id'],
                    'currency' => ['USD', 'EUR'],
                    'unit' => 'liter'
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customerprices',
                        'id' => '<(implode("-", [@customer.level_1->id, @US->id, @product-1->id, "USD-liter-10"]))>',
                        'attributes' => [
                            'currency' => 'USD',
                            'quantity' => 10,
                            'value' => '1.2000'
                        ],
                        'relationships' => [
                            'unit' => [
                                'data' => ['type' => 'productunits', 'id' => 'liter']
                            ],
                            'product' => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                            ],
                            'customer' => [
                                'data' => ['type' => 'customers', 'id' => '<toString(@customer.level_1->id)>']
                            ],
                            'website' => [
                                'data' => ['type' => 'websites', 'id' => '<toString(@US->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListForUnauthorizedUser(): void
    {
        $response = $this->cget(
            ['entity' => 'customerprices'],
            [
                'filter' => [
                    'customer' => 0,
                    'website' => '@US->id',
                    'product' => ['@product-1->id'],
                    'unit' => 'liter'
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customerprices',
                        'id' => '<(implode("-", ["0", @US->id, @product-1->id, "USD-liter-10"]))>',
                        'attributes' => [
                            'currency' => 'USD',
                            'quantity' => 10,
                            'value' => '1.2000'
                        ],
                        'relationships' => [
                            'unit' => [
                                'data' => ['type' => 'productunits', 'id' => 'liter']
                            ],
                            'product' => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                            ],
                            'customer' => [
                                'data' => null
                            ],
                            'website' => [
                                'data' => ['type' => 'websites', 'id' => '<toString(@US->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithFieldsFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'customerprices'],
            [
                'filter' => [
                    'customer' => '@customer.level_1->id',
                    'website' => '@US->id',
                    'product' => '@product-1->id',
                    'unit' => 'liter'
                ],
                'fields[customerprices]' => 'quantity,value'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customerprices',
                        'id' => '<(implode("-", [@customer.level_1->id, @US->id, @product-1->id, "USD-liter-10"]))>',
                        'attributes' => [
                            'quantity' => 10,
                            'value' => '1.2000'
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('currency', $responseContent['data'][0]['attributes']);
        self::assertArrayNotHasKey('relationships', $responseContent['data'][0]);
    }

    public function testGetListWithIncludeFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'customerprices'],
            [
                'filter' => [
                    'customer' => '@customer.level_1->id',
                    'website' => '@US->id',
                    'product' => '@product-1->id'
                ],
                'include' => 'unit,product,customer,website'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customerprices',
                        'id' => '<(implode("-", [@customer.level_1->id, @US->id, @product-1->id, "USD-bottle-1"]))>',
                        'attributes' => [
                            'currency' => 'USD',
                            'quantity' => 1,
                            'value' => '1.1000'
                        ],
                        'relationships' => [
                            'unit' => [
                                'data' => ['type' => 'productunits', 'id' => 'bottle']
                            ],
                            'product' => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                            ],
                            'customer' => [
                                'data' => ['type' => 'customers', 'id' => '<toString(@customer.level_1->id)>']
                            ],
                            'website' => [
                                'data' => ['type' => 'websites', 'id' => '<toString(@US->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => 'customerprices',
                        'id' => '<(implode("-", [@customer.level_1->id, @US->id, @product-1->id, "USD-liter-10"]))>',
                        'attributes' => [
                            'currency' => 'USD',
                            'quantity' => 10,
                            'value' => '1.2000'
                        ],
                        'relationships' => [
                            'unit' => [
                                'data' => ['type' => 'productunits', 'id' => 'liter']
                            ],
                            'product' => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                            ],
                            'customer' => [
                                'data' => ['type' => 'customers', 'id' => '<toString(@customer.level_1->id)>']
                            ],
                            'website' => [
                                'data' => ['type' => 'websites', 'id' => '<toString(@US->id)>']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'products',
                        'id' => '<toString(@product-1->id)>',
                        'attributes' => ['sku' => 'product-1']
                    ],
                    [
                        'type' => 'customers',
                        'id' => '<toString(@customer.level_1->id)>',
                        'attributes' => ['name' => 'customer.level_1']
                    ],
                    [
                        'type' => 'websites',
                        'id' => '<toString(@US->id)>',
                        'attributes' => ['name' => 'US']
                    ],
                    [
                        'type' => 'productunits',
                        'id' => 'bottle',
                        'attributes' => ['label' => 'bottle']
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListForNotExistingWebsite(): void
    {
        $response = $this->cget(
            ['entity' => 'customerprices'],
            ['filter' => ['website' => 999999, 'customer' => '@customer.level_1->id', 'product' => ['@product-1->id']]]
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testGetListForNotExistingCustomer(): void
    {
        $response = $this->cget(
            ['entity' => 'customerprices'],
            ['filter' => ['customer' => 999999, 'website' => '@US->id', 'product' => ['@product-1->id']]]
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testGetListForNotExistingProduct(): void
    {
        $response = $this->cget(
            ['entity' => 'customerprices'],
            ['filter' => ['product' => 999999, 'customer' => '@customer.level_1->id', 'website' => '@US->id']]
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testTryToGet(): void
    {
        $response = $this->get(
            [
                'entity' => 'customerprices',
                'id' => '<(implode("-", [@customer.level_1->id, @US->id, @product-1->id, "USD-bottle-1"]))>'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'customerprices'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            [
                'entity' => 'customerprices',
                'id' => '<(implode("-", [@customer.level_1->id, @US->id, @product-1->id, "USD-bottle-1"]))>'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            [
                'entity' => 'customerprices',
                'id' => '<(implode("-", [@customer.level_1->id, @US->id, @product-1->id, "USD-bottle-1"]))>'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'customerprices'],
            [
                'filter' => [
                    'id' => '<(implode("-", [@customer.level_1->id, @US->id, @product-1->id, "USD-bottle-1"]))>'
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetListForNotAccessibleCustomer(): void
    {
        $this->updateRolePermission('ROLE_ADMINISTRATOR', Customer::class, AccessLevel::NONE_LEVEL);

        $response = $this->cget(
            ['entity' => 'customerprices'],
            [
                'filter' => [
                    'customer' => '@customer.level_1->id',
                    'website' => '@US->id',
                    'product' => '@product-1->id'
                ]
            ]
        );

        $this->assertResponseContains(['data' => []], $response);
    }
}
