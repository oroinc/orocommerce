<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductKitCombinedPriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductKitCombinedProductPrices;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ProductKitPriceTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    #[\Override]
    protected function setUp(): void
    {
        self::markTestSkipped('Must be fixed and unskipped in BB-25285');
        parent::setUp();

        $this->loadFixtures([
            LoadProductKitData::class,
            LoadCombinedProductPrices::class,
            LoadProductKitCombinedProductPrices::class,
            LoadProductKitCombinedPriceList::class
        ]);
    }

    public function testTryToGetListWithoutRequiredFilters(): void
    {
        $response = $this->cget(['entity' => 'productkitprices'], [], [], false);

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'filter constraint',
                    'detail' => 'The "customer" filter is required.',
                ],
                [
                    'title' => 'filter constraint',
                    'detail' => 'The "website" filter is required.',
                ],
                [
                    'title' => 'filter constraint',
                    'detail' => 'The "product" filter is required.',
                ],
                [
                    'title' => 'filter constraint',
                    'detail' => 'The "quantity" filter is required.',
                ],
                [
                    'title' => 'filter constraint',
                    'detail' => 'The "unit" filter is required.',
                ]
            ],
            $response
        );
    }

    public function testTryToGetListWithInvalidCustomerFilterValue(): void
    {
        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'customer' => 'text',
                'website' => '@US->id',
                'product' => '@product-kit-1->id',
                'unit' => 'milliliter',
                'quantity' => 1
            ]],
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
            ['entity' => 'productkitprices'],
            ['filter' => [
                'customer' => '@customer.level_1->id',
                'website' => '@US->id',
                'product' => 'text',
                'unit' => 'milliliter',
                'quantity' => 1
            ]],
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
            ['entity' => 'productkitprices'],
            ['filter' => [
                'customer' => '@customer.level_1->id',
                'website' => 'text',
                'product' => '@product-kit-1->id',
                'unit' => 'milliliter',
                'quantity' => 1
            ]],
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

    public function testTryToGetListWithNoProductKit(): void
    {
        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'website' => '@US->id',
                'customer' => '@customer.level_1->id',
                'product' => '@product-1->id',
                'unit' => 'milliliter',
                'quantity' => 1,
            ]],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'status' => '400',
                'title' => 'value constraint',
                'detail' => 'The resource supports only "kit" products.',
            ],
            $response
        );
    }

    public function testTryToGetListWithoutRequiredKitItemFilters(): void
    {
        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'website' => '@US->id',
                'customer' => '@customer.level_1->id',
                'product' => '@product-kit-1->id',
                'unit' => 'milliliter',
                'quantity' => 1,
            ]],
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'filter constraint',
                    'detail' => "The \"filter[kitItems][$kitItemId][product]\" filter is required.",
                ],
                [
                    'title' => 'filter constraint',
                    'detail' => "The \"filter[kitItems][$kitItemId][quantity]\" filter is required.",
                ],
            ],
            $response
        );
    }

    public function testTryToGetListWithSkippedRequiredKitItemFilter(): void
    {
        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'website' => '@US->id',
                'customer' => '@customer.level_1->id',
                'product' => '@product-kit-1->id',
                'unit' => 'milliliter',
                'quantity' => 1,
                "kitItems.$kitItemId.product" => '@product-1->id',
            ]],
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'filter constraint',
                    'detail' => "The \"filter[kitItems][$kitItemId][quantity]\" filter is required.",
                ],
            ],
            $response
        );
    }

    public function testTryToGetListWithNoSupportedKitItemFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'website' => '@US->id',
                'customer' => '@customer.level_1->id',
                'product' => '@product-kit-1->id',
                'unit' => 'milliliter',
                'quantity' => 1,
                'kitItems.text.product' => '@product-1->id',
            ]],
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'filter constraint',
                    'detail' => "The filter is not supported.",
                    'source' => ['parameter' => 'filter[kitItems.text.product]']
                ],
            ],
            $response
        );
    }

    public function testTryToGetListWithNoBelongingKitItem(): void
    {
        $productKit = $this->getReference('product-kit-1');
        $kitItemId = $productKit->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'website' => '@US->id',
                'customer' => '@customer.level_1->id',
                'product' => '@product-kit-1->id',
                'unit' => 'milliliter',
                'quantity' => 1,
                'kitItems.999.product' => '@product-1->id',
                'kitItems.999.quantity' => 1,
            ]],
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'filter constraint',
                    'detail' => "The \"filter[kitItems][$kitItemId][product]\" filter is required.",
                ],
                [
                    'title' => 'filter constraint',
                    'detail' => "The \"filter[kitItems][$kitItemId][quantity]\" filter is required.",
                ],
                [
                    'title' => 'value constraint',
                    'detail' => "The kit item #999 does not belong to product kit #{$productKit->getId()}.",
                ]
            ],
            $response
        );
    }

    public function testTryToGetListWithSkippedOptionalKitItemFilter(): void
    {
        $kitItems = $this->getReference('product-kit-2')->getKitItems();
        $requiredKitItemId = $optionalKitItemId = null;
        foreach ($kitItems as $kitItem) {
            $optionalKitItemId ??= $kitItem->isOptional() ? $kitItem->getId() : null;
            $requiredKitItemId ??= !$kitItem->isOptional() ? $kitItem->getId() : null;
        }

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'website' => '@US->id',
                'customer' => '@customer.level_1->id',
                'product' => '@product-kit-2->id',
                'unit' => 'milliliter',
                'quantity' => 1,
                "kitItems.$requiredKitItemId.product" => '@product-1->id',
                "kitItems.$requiredKitItemId.quantity" => 1,
                "kitItems.$optionalKitItemId.quantity" => 1,
            ]],
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'filter constraint',
                    'detail' => "The \"filter[kitItems][$optionalKitItemId][product]\" filter is missed.",
                ],
            ],
            $response
        );
    }

    public function testTryToGetListWithNoBelongingKitItemProductToKitItem(): void
    {
        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'website' => '@US->id',
                'customer' => '@customer.level_1->id',
                'product' => '@product-kit-1->id',
                'unit' => 'milliliter',
                'quantity' => 1,
                "kitItems.$kitItemId.product" => 999,
                "kitItems.$kitItemId.quantity" => 1,
            ]],
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'value constraint',
                    'detail' => "The kit item product #999 does not belong to kit item #$kitItemId.",
                ]
            ],
            $response
        );
    }

    public function testTryToGetListWithInvalidKitItemProductValue(): void
    {
        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'website' => '@US->id',
                'customer' => '@customer.level_1->id',
                'product' => '@product-kit-1->id',
                'unit' => 'milliliter',
                'quantity' => 1,
                "kitItems.$kitItemId.product" => 'text',
                "kitItems.$kitItemId.quantity" => 1,
            ]],
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'filter constraint',
                    'detail' => "Expected integer value. Given \"text\".",
                    'source' => ['parameter' => "filter[kitItems.$kitItemId.product]"]
                ]
            ],
            $response
        );
    }

    public function testTryToGetListWithInvalidKitItemQuantityValue(): void
    {
        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'website' => '@US->id',
                'customer' => '@customer.level_1->id',
                'product' => '@product-kit-1->id',
                'unit' => 'milliliter',
                'quantity' => 1,
                "kitItems.$kitItemId.product" => '@product-1->id',
                "kitItems.$kitItemId.quantity" => 'text',
            ]],
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'filter constraint',
                    'detail' => "Expected number value. Given \"text\".",
                    'source' => ['parameter' => "filter[kitItems.$kitItemId.quantity]"]
                ]
            ],
            $response
        );
    }

    public function testTryToGetListWithNoValidKitItemQuantityRangeValue(): void
    {
        $kitItems = $this->getReference('product-kit-3')->getKitItems();
        $kitItemId = $kitItems->get(0)->getId();
        $kitItemId2 = $kitItems->get(1)->getId();
        $kitItemId3 = $kitItems->get(2)->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'website' => '@US->id',
                'customer' => '@customer.level_1->id',
                'product' => '@product-kit-3->id',
                'unit' => 'milliliter',
                'quantity' => 1,
                "kitItems.$kitItemId.product" => '@product-1->id',
                "kitItems.$kitItemId.quantity" => 3,
                "kitItems.$kitItemId2.product" => '@product-3->id',
                "kitItems.$kitItemId2.quantity" => 1,
                "kitItems.$kitItemId3.product" => '@product-4->id',
                "kitItems.$kitItemId3.quantity" => 5,
            ]],
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'value constraint',
                    'detail' => "The \"filter[kitItems][$kitItemId][quantity]\"" .
                        " filter value should be between 1 and 2.",
                ],
                [
                    'title' => 'value constraint',
                    'detail' => "The \"filter[kitItems][$kitItemId2][quantity]\"" .
                        " filter value should be equals to or exceed 2.",
                ],
                [
                    'title' => 'value constraint',
                    'detail' => "The \"filter[kitItems][$kitItemId3][quantity]\"" .
                        " filter value should be equals to or less than 4.",
                ]
            ],
            $response
        );
    }

    public function testGetList(): void
    {
        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            [
                'filter' => [
                    'website' => '@US->id',
                    'customer' => '@customer.level_1->id',
                    'product' => '@product-kit-1->id',
                    'unit' => 'milliliter',
                    'quantity' => 1,
                    "kitItems.$kitItemId.product" => '@product-1->id',
                    "kitItems.$kitItemId.quantity" => 1,
                ]
            ]
        );

        $priceId = '<(implode("-", [@customer.level_1->id, @US->id, @product-kit-1->id, "USD-milliliter-1"]))>';
        $itemId = "<(implode('-', [$kitItemId, @customer.level_1->id, @US->id, @product-1->id, 'USD-milliliter-1']))>";

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productkitprices',
                        'id' => $priceId,
                        'attributes' => [
                            'currency' => 'USD',
                            'quantity' => 1,
                            'value' => '30.0000'
                        ],
                        'relationships' => [
                            'unit' => [
                                'data' => ['type' => 'productunits', 'id' => 'milliliter']
                            ],
                            'product' => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product-kit-1->id)>']
                            ],
                            'customer' => [
                                'data' => ['type' => 'customers', 'id' => '<toString(@customer.level_1->id)>']
                            ],
                            'website' => [
                                'data' => ['type' => 'websites', 'id' => '<toString(@US->id)>']
                            ],
                            'kitItemPrices' => [
                                'data' => [['type' => 'productkititemprices', 'id' => $itemId]]
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
        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            [
                'filter' => [
                    'website' => '@US->id',
                    'customer' => 0,
                    'product' => '@product-kit-1->id',
                    'unit' => 'milliliter',
                    'quantity' => 1,
                    "kitItems.$kitItemId.product" => '@product-1->id',
                    "kitItems.$kitItemId.quantity" => 1,
                ]
            ]
        );

        $itemId = "<(implode('-', [$kitItemId, '0', @US->id, @product-1->id, 'USD-milliliter-1']))>";

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productkitprices',
                        'id' => '<(implode("-", ["0", @US->id, @product-kit-1->id, "USD-milliliter-1"]))>',
                        'attributes' => [
                            'currency' => 'USD',
                            'quantity' => 1,
                            'value' => '30.0000'
                        ],
                        'relationships' => [
                            'unit' => [
                                'data' => ['type' => 'productunits', 'id' => 'milliliter']
                            ],
                            'product' => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product-kit-1->id)>']
                            ],
                            'customer' => [
                                'data' => null
                            ],
                            'website' => [
                                'data' => ['type' => 'websites', 'id' => '<toString(@US->id)>']
                            ],
                            'kitItemPrices' => [
                                'data' => [['type' => 'productkititemprices', 'id' => $itemId]]
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
        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            [
                'filter' => [
                    'website' => '@US->id',
                    'customer' => '@customer.level_1->id',
                    'product' => '@product-kit-1->id',
                    'unit' => 'milliliter',
                    'quantity' => 1,
                    "kitItems.$kitItemId.product" => '@product-1->id',
                    "kitItems.$kitItemId.quantity" => 1,
                ],
                'fields[productkitprices]' => 'quantity,value'
            ]
        );

        $priceId = '<(implode("-", [@customer.level_1->id, @US->id, @product-kit-1->id, "USD-milliliter-1"]))>';

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productkitprices',
                        'id' => $priceId,
                        'attributes' => [
                            'quantity' => 1,
                            'value' => '30.0000'
                        ],
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithIncludeFilter(): void
    {
        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            [
                'filter' => [
                    'website' => '@US->id',
                    'customer' => '@customer.level_1->id',
                    'product' => '@product-kit-1->id',
                    'unit' => 'milliliter',
                    'quantity' => 1,
                    "kitItems.$kitItemId.product" => '@product-1->id',
                    "kitItems.$kitItemId.quantity" => 1,
                ],
                'include' => 'kitItemPrices,product,customer,website,unit'
            ]
        );

        $priceId = '<(implode("-", [@customer.level_1->id, @US->id, @product-kit-1->id, "USD-milliliter-1"]))>';
        $itemId = "<(implode('-', [$kitItemId, @customer.level_1->id, @US->id, @product-1->id, 'USD-milliliter-1']))>";

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productkitprices',
                        'id' => $priceId,
                        'attributes' => [
                            'currency' => 'USD',
                            'quantity' => 1,
                            'value' => '30.0000'
                        ],
                        'relationships' => [
                            'unit' => [
                                'data' => ['type' => 'productunits', 'id' => 'milliliter']
                            ],
                            'product' => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product-kit-1->id)>']
                            ],
                            'customer' => [
                                'data' => ['type' => 'customers', 'id' => '<toString(@customer.level_1->id)>']
                            ],
                            'website' => [
                                'data' => ['type' => 'websites', 'id' => '<toString(@US->id)>']
                            ],
                            'kitItemPrices' => [
                                'data' => [['type' => 'productkititemprices', 'id' => $itemId]]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'products',
                        'id' => '<toString(@product-kit-1->id)>',
                        'attributes' => ['sku' => 'product-kit-1']
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
                        'id' => 'milliliter',
                        'attributes' => ['label' => 'milliliter']
                    ],
                    [
                        'type' => 'productkititemprices',
                        'id' => $itemId,
                        'attributes' => ['currency' => 'USD', 'quantity' => 1, 'value' => '10.0000'],
                        'relationships' => [
                            'product' => ['data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']],
                            'customer' => ['data' => [
                                'type' => 'customers',
                                'id' => '<toString(@customer.level_1->id)>'
                            ]],
                            'website' => ['data' => ['type' => 'websites', 'id' => '<toString(@US->id)>']],
                            'unit' => ['data' => ['type' => 'productunits', 'id' => 'milliliter']],
                            'kitItem' => ['data' => ['type' => 'productkititems', 'id' => (string)$kitItemId]]
                        ],
                    ]
                ],
            ],
            $response
        );
    }

    public function testGetListWithNoSupportedCurrency(): void
    {
        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'website' => '@US->id',
                'customer' => '@customer.level_1->id',
                'product' => '@product-kit-1->id',
                'unit' => 'milliliter',
                'quantity' => 1,
                'currency' => 'EUR',
                "kitItems.$kitItemId.product" => '@product-1->id',
                "kitItems.$kitItemId.quantity" => 1,
            ]],
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testGetListWithNoSupportedProductUnit(): void
    {
        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'website' => '@US->id',
                'customer' => '@customer.level_1->id',
                'product' => '@product-kit-1->id',
                'unit' => 'box',
                'quantity' => 1,
                'currency' => 'USD',
                "kitItems.$kitItemId.product" => '@product-1->id',
                "kitItems.$kitItemId.quantity" => 1,
            ]],
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testGetListWithInvalidQuantityFilterValue(): void
    {
        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'website' => '@US->id',
                'customer' => '@customer.level_1->id',
                'product' => '@product-kit-1->id',
                'unit' => 'milliliter',
                'quantity' => -1,
                "kitItems.$kitItemId.product" => '@product-1->id',
                "kitItems.$kitItemId.quantity" => 1,
            ]],
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testGetListWithFloatQuantityFilterValue(): void
    {
        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'website' => '@US->id',
                'customer' => '@customer.level_1->id',
                'product' => '@product-kit-1->id',
                'unit' => 'milliliter',
                'quantity' => '0.1',
                "kitItems.$kitItemId.product" => '@product-1->id',
                "kitItems.$kitItemId.quantity" => 1,
            ]],
        );

        $priceId = '<(implode("-", [@customer.level_1->id, @US->id, @product-kit-1->id, "USD-milliliter-1"]))>';
        $itemId = "<(implode('-', [$kitItemId, @customer.level_1->id, @US->id, @product-1->id, 'USD-milliliter-1']))>";

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productkitprices',
                        'id' => $priceId,
                        'attributes' => [
                            'currency' => 'USD',
                            'quantity' => 1,
                            'value' => '10.0000'
                        ],
                        'relationships' => [
                            'unit' => [
                                'data' => ['type' => 'productunits', 'id' => 'milliliter']
                            ],
                            'product' => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product-kit-1->id)>']
                            ],
                            'customer' => [
                                'data' => ['type' => 'customers', 'id' => '<toString(@customer.level_1->id)>']
                            ],
                            'website' => [
                                'data' => ['type' => 'websites', 'id' => '<toString(@US->id)>']
                            ],
                            'kitItemPrices' => [
                                'data' => [['type' => 'productkititemprices', 'id' => $itemId]]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithFloatKitItemQuantityValue(): void
    {
        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'website' => '@US->id',
                'customer' => '@customer.level_1->id',
                'product' => '@product-kit-1->id',
                'unit' => 'milliliter',
                'quantity' => '0.1',
                "kitItems.$kitItemId.product" => '@product-1->id',
                "kitItems.$kitItemId.quantity" => '0.5',
            ]],
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testGetListForNotExistingUnit(): void
    {
        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'website' => '@US->id',
                'customer' => '@customer.level_1->id',
                'product' => '@product-kit-1->id',
                'unit' => 'test',
                'quantity' => 1,
                "kitItems.$kitItemId.product" => '@product-1->id',
                "kitItems.$kitItemId.quantity" => 1,
            ]]
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testGetListForNotExistingWebsite(): void
    {
        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'website' => 999999,
                'customer' => '@customer.level_1->id',
                'product' => '@product-kit-1->id',
                'unit' => 'milliliter',
                'quantity' => 1,
                "kitItems.$kitItemId.product" => '@product-1->id',
                "kitItems.$kitItemId.quantity" => 1,
            ]]
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testGetListForNotExistingCustomer(): void
    {
        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'customer' => 999999,
                'website' => '@US->id',
                'product' => '@product-kit-1->id',
                'unit' => 'milliliter',
                'quantity' => 1,
                "kitItems.$kitItemId.product" => '@product-1->id',
                "kitItems.$kitItemId.quantity" => 1,
            ]]
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testGetListForNotExistingProduct(): void
    {
        $response = $this->cget(
            ['entity' => 'productkitprices'],
            ['filter' => [
                'product' => 999999,
                'customer' => '@customer.level_1->id',
                'website' => '@US->id',
                'unit' => 'milliliter',
                'quantity' => 1,
            ]]
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testTryToGet(): void
    {
        $response = $this->get(
            [
                'entity' => 'productkitprices',
                'id' => '<(implode("-", [@customer.level_1->id, @US->id, @product-kit-1->id, "USD-milliliter-1"]))>'
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
            ['entity' => 'productkitprices'],
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
                'entity' => 'productkitprices',
                'id' => '<(implode("-", [@customer.level_1->id, @US->id, @product-kit-1->id, "USD-milliliter-1"]))>'
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
                'entity' => 'productkitprices',
                'id' => '<(implode("-", [@customer.level_1->id, @US->id, @product-kit-2->id, "USD-milliliter-1"]))>'
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
            ['entity' => 'productkitprices'],
            [
                'filter' => [
                    'id' => '<(implode("-", [@customer.level_1->id, @US->id, @product-kit-1->id, "USD-milliliter-1"]))>'
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

        $kitItemId = $this->getReference('product-kit-1')->getKitItems()->first()->getId();

        $response = $this->cget(
            ['entity' => 'productkitprices'],
            [
                'filter' => [
                    'customer' => '@customer.level_1->id',
                    'website' => '@US->id',
                    'product' => '@product-kit-1->id',
                    'unit' => 'milliliter',
                    'quantity' => 1,
                    "kitItems.$kitItemId.product" => '@product-1->id',
                    "kitItems.$kitItemId.quantity" => 1,
                ]
            ]
        );

        $this->assertResponseContains(['data' => []], $response);
    }
}
