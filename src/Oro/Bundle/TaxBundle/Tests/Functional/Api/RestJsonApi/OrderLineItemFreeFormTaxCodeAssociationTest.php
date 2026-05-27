<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadOrderLineItemFreeFormTaxCodes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;

/**
 * @dbIsolationPerTest
 */
class OrderLineItemFreeFormTaxCodeAssociationTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadOrders::class,
            LoadProductData::class,
            LoadOrderLineItemFreeFormTaxCodes::class,
        ]);
    }

    public function testGetOrderLineItemWithFreeFormTaxCode(): void
    {
        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order_line_item.1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'orderlineitems',
                    'id' => '<toString(@order_line_item.1->id)>',
                    'relationships' => [
                        'freeFormTaxCode' => [
                            'data' => [
                                'type' => 'producttaxcodes',
                                'id' => '<toString(@product_tax_code.TAX1->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetOrderLineItemWithoutFreeFormTaxCode(): void
    {
        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order_line_item.2->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'orderlineitems',
                    'id' => '<toString(@order_line_item.2->id)>',
                    'relationships' => [
                        'freeFormTaxCode' => [
                            'data' => null
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateOrderLineItemWithFreeFormTaxCode(): void
    {
        $lineItemId = $this->getReference(LoadOrderLineItemFreeFormTaxCodes::ORDER_LINE_ITEM_WITH_TAX_CODE)->getId();
        $taxCodeId = $this->getReference(
            LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_2
        )->getId();
        $data = [
            'data' => [
                'type' => 'orderlineitems',
                'id' => (string)$lineItemId,
                'relationships' => [
                    'freeFormTaxCode' => [
                        'data' => [
                            'type' => 'producttaxcodes',
                            'id' => (string)$taxCodeId
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order_line_item.1->id)>'],
            $data
        );

        $this->assertResponseContains($data, $response);

        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        self::assertEquals($taxCodeId, $lineItem->getFreeFormTaxCode()->getId());
    }

    public function testUpdateOrderLineItemWithNullFreeFormTaxCode(): void
    {
        $lineItemId = $this->getReference(LoadOrderLineItemFreeFormTaxCodes::ORDER_LINE_ITEM_WITH_TAX_CODE)->getId();
        $data = [
            'data' => [
                'type' => 'orderlineitems',
                'id' => (string)$lineItemId,
                'relationships' => [
                    'freeFormTaxCode' => [
                        'data' => null
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order_line_item.1->id)>'],
            $data
        );

        $this->assertResponseContains($data, $response);

        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        self::assertNull($lineItem->getFreeFormTaxCode());
    }
}
