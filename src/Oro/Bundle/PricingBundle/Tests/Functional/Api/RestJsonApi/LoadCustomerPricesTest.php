<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;

/**
 * @dbIsolationPerTest
 */
class LoadCustomerPricesTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadCombinedProductPrices::class
        ]);
    }

    public function testGetListWithoutFilters(): void
    {
        $response = $this->cget(['entity' => 'customerprices'], assertValid: false);

        $this->assertResponseValidationErrors(
            [
                [
                    'status'  => '400',
                    'title' => 'filter constraint',
                    'detail' => 'The "customer" filter is required.',
                ], [
                    'status'  => '400',
                    'title' => 'filter constraint',
                    'detail' => 'The "website" filter is required.',
                ], [
                    'status'  => '400',
                    'title' => 'filter constraint',
                    'detail' => 'The "product" filter is required.',
                ]
            ],
            $response
        );
    }

    public function testGetListWithoutProductFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'customerprices'],
            ['filter' => ['customer' => '1', 'website' => '1']],
            [],
            false
        );

        $this->assertResponseValidationError(
            ['status'  => '400', 'title' => 'filter constraint', 'detail' => 'The "product" filter is required.'],
            $response
        );
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'customerprices'],
            ['filter' => [
                'customer' => '@customer.level_1->id',
                'website' => '@US->id',
                'product' => ['@product-1->id']
            ]],
        );

        $this->assertResponseContains('customer_price/get_list.yml', $response);
    }

    public function testGetListWithAllFilters(): void
    {
        $response = $this->cget(
            ['entity' => 'customerprices'],
            ['filter' => [
                'customer' => '@customer.level_1->id',
                'website' => '@US->id',
                'product' => ['@product-1->id', '@product-2->id', '@product-3->id', '@product-4->id'],
                'currency' => ['USD', 'EUR'],
                'unit' => 'liter'
            ]],
        );

        $this->assertResponseContains('customer_price/get_list_with_all_filters.yml', $response);
    }

    /**
     * @dataProvider getListWithNotValidValuesDataProvider
     */
    public function testGetListWithNotValidWebsiteValues(array $expected, bool $assertValid, mixed $value): void
    {
        $response = $this->cget(
            ['entity' => 'customerprices'],
            ['filter' => ['customer' => '@customer.level_1->id', 'website' => $value, 'product' => ['@product-1->id']]],
            assertValid: $assertValid
        );

        if ($assertValid) {
            $this->assertResponseContains($expected, $response);
        } else {
            $this->assertResponseValidationErrors($expected, $response);
        }
    }

    /**
     * @dataProvider getListWithNotValidValuesDataProvider
     */
    public function testGetListWithNotValidCustomerValues(array $expected, bool $assertValid, mixed $value): void
    {
        $response = $this->cget(
            ['entity' => 'customerprices'],
            ['filter' => ['customer' => $value, 'website' => '@US->id', 'product' => ['@product-1->id']]],
            assertValid: $assertValid
        );

        if ($assertValid) {
            $this->assertResponseContains($expected, $response);
        } else {
            $this->assertResponseValidationErrors($expected, $response);
        }
    }

    /**
     * @dataProvider getListWithNotValidValuesDataProvider
     */
    public function testGetListWithNotValidProductValues(array $expected, bool $assertValid, mixed $value): void
    {
        $response = $this->cget(
            ['entity' => 'customerprices'],
            ['filter' => ['customer' => '@customer.level_1->id', 'website' => '@US->id', 'product' => [$value]]],
            assertValid: $assertValid
        );

        if ($assertValid) {
            $this->assertResponseContains($expected, $response);
        } else {
            $this->assertResponseValidationErrors($expected, $response);
        }
    }

    public function getListWithNotValidValuesDataProvider(): array
    {
        return [
            'zero' => [
                'expected' => [],
                'assertValid' => true,
                'value' => 0,
            ],
            'minus' => [
                'expected' => [],
                'assertValid' => true,
                'value' => -10,
            ],
            'wrong id' => [
                'expected' => [],
                'assertValid' => true,
                'value' => 999999,
            ],
            'text' => [
                'expected' => [
                    [
                        'status'  => '400',
                        'title' => 'filter constraint',
                        'detail' => 'Expected integer value. Given "text".',
                    ]
                ],
                'assertValid' => false,
                'value' => 'text',
            ]
        ];
    }
}
