<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiForOrderLineItem\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiForOrderLineItem\DataFixtures\LoadOrderLineItemCustomerFiltersData;

class OrderLineItemCustomerFiltersTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadOrderLineItemCustomerFiltersData::class
        ]);
    }

    /**
     * @dataProvider customerFilterDataProvider
     */
    public function testGetListFilterByCustomer(array $customerRefs, string $filterKey, array $expectedIds): void
    {
        $filterValue = implode(
            ',',
            array_map(fn ($ref) => $this->getReference($ref)->getId(), $customerRefs)
        );

        $response = $this->cget(
            ['entity' => 'orderlineitems'],
            [$filterKey => $filterValue]
        );

        $expectedData = array_map(fn ($id) => ['type' => 'orderlineitems', 'id' => $id], $expectedIds);

        $this->assertResponseContains(
            ['data' => $expectedData],
            $response
        );
        static::assertResponseCount(count($expectedIds), $response);
    }

    public function customerFilterDataProvider(): array
    {
        return [
            'Filter by customer 1 of level 2 with default eq' => [
                'customerRefs' => [LoadCustomers::CUSTOMER_LEVEL_1_DOT_1],
                'filterKey' => 'filter[customer]',
                'expectedIds' => [
                    '<toString(@line_item_customer_1_product_1->id)>',
                    '<toString(@line_item_customer_1_product_2->id)>',
                ],
            ],
            'Filter by customer 2 of level 2 with default eq' => [
                'customerRefs' => [LoadCustomers::CUSTOMER_LEVEL_1_DOT_2],
                'filterKey' => 'filter[customer]',
                'expectedIds' => [
                    '<toString(@line_item_customer_2_product_1->id)>',
                    '<toString(@line_item_customer_2_product_2->id)>',
                ],
            ],
            'Filter by both customers of level 2 with default eq' => [
                'customerRefs' => [
                    LoadCustomers::CUSTOMER_LEVEL_1_DOT_1,
                    LoadCustomers::CUSTOMER_LEVEL_1_DOT_2,
                ],
                'filterKey' => 'filter[customer]',
                'expectedIds' => [
                    '<toString(@line_item_customer_1_product_1->id)>',
                    '<toString(@line_item_customer_1_product_2->id)>',
                    '<toString(@line_item_customer_2_product_1->id)>',
                    '<toString(@line_item_customer_2_product_2->id)>',
                ],
            ],
            'Filter by customer 1 of level 2 with neq' => [
                'customerRefs' => [LoadCustomers::CUSTOMER_LEVEL_1_DOT_1],
                'filterKey' => 'filter[customer][neq]',
                'expectedIds' => [
                    '<toString(@line_item_customer_2_product_1->id)>',
                    '<toString(@line_item_customer_2_product_2->id)>',
                ],
            ],
            'Filter by customer 2 of level 2 with neq' => [
                'customerRefs' => [LoadCustomers::CUSTOMER_LEVEL_1_DOT_2],
                'filterKey' => 'filter[customer][neq]',
                'expectedIds' => [
                    '<toString(@line_item_customer_1_product_1->id)>',
                    '<toString(@line_item_customer_1_product_2->id)>',
                ],
            ],
            'Filter by both customers of level 2 with neq' => [
                'customerRefs' => [
                    LoadCustomers::CUSTOMER_LEVEL_1_DOT_1,
                    LoadCustomers::CUSTOMER_LEVEL_1_DOT_2,
                ],
                'filterKey' => 'filter[customer][neq]',
                'expectedIds' => [],
            ],
        ];
    }

    /**
     * @dataProvider customerUserFilterDataProvider
     */
    public function testGetListFilterByCustomerUser(
        array $customerUserRefs,
        string $filterKey,
        array $expectedIds
    ): void {
        $filterValue = implode(
            ',',
            array_map(fn ($ref) => $this->getReference($ref)->getId(), $customerUserRefs)
        );

        $response = $this->cget(
            ['entity' => 'orderlineitems'],
            [$filterKey => $filterValue]
        );

        $expectedData = array_map(fn ($id) => ['type' => 'orderlineitems', 'id' => $id], $expectedIds);

        $this->assertResponseContains(
            ['data' => $expectedData],
            $response
        );
        static::assertResponseCount(count($expectedIds), $response);
    }

    public function customerUserFilterDataProvider(): array
    {
        return [
            'Filter by customer user 1 with default eq' => [
                'customerUserRefs' => [LoadCustomerUserData::LEVEL_1_1_EMAIL],
                'filterKey' => 'filter[customerUser]',
                'expectedIds' => [
                    '<toString(@line_item_customer_1_product_1->id)>',
                    '<toString(@line_item_customer_1_product_2->id)>',
                ],
            ],
            'Filter by customer user 2 with default eq' => [
                'customerUserRefs' => [LoadCustomerUserData::GROUP2_EMAIL],
                'filterKey' => 'filter[customerUser]',
                'expectedIds' => [
                    '<toString(@line_item_customer_2_product_1->id)>',
                    '<toString(@line_item_customer_2_product_2->id)>',
                ],
            ],
            'Filter by both customer users with default eq' => [
                'customerUserRefs' => [
                    LoadCustomerUserData::LEVEL_1_1_EMAIL,
                    LoadCustomerUserData::GROUP2_EMAIL,
                ],
                'filterKey' => 'filter[customerUser]',
                'expectedIds' => [
                    '<toString(@line_item_customer_1_product_1->id)>',
                    '<toString(@line_item_customer_1_product_2->id)>',
                    '<toString(@line_item_customer_2_product_1->id)>',
                    '<toString(@line_item_customer_2_product_2->id)>',
                ],
            ],
            'Filter by customer user 1 with neq' => [
                'customerUserRefs' => [LoadCustomerUserData::LEVEL_1_1_EMAIL],
                'filterKey' => 'filter[customerUser][neq]',
                'expectedIds' => [
                    '<toString(@line_item_customer_2_product_1->id)>',
                    '<toString(@line_item_customer_2_product_2->id)>',
                ],
            ],
            'Filter by customer user 2 with neq' => [
                'customerUserRefs' => [LoadCustomerUserData::GROUP2_EMAIL],
                'filterKey' => 'filter[customerUser][neq]',
                'expectedIds' => [
                    '<toString(@line_item_customer_1_product_1->id)>',
                    '<toString(@line_item_customer_1_product_2->id)>',
                ],
            ],
            'Filter by both customer users with neq' => [
                'customerUserRefs' => [
                    LoadCustomerUserData::LEVEL_1_1_EMAIL,
                    LoadCustomerUserData::GROUP2_EMAIL,
                ],
                'filterKey' => 'filter[customerUser][neq]',
                'expectedIds' => [],
            ],
        ];
    }
}
