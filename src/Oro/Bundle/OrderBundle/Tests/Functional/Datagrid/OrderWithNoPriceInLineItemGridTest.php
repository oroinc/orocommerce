<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Datagrid;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderWithNoPriceInLineItem;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * Test to verify that order line items with null prices are handled correctly in the grid
 */
class OrderWithNoPriceInLineItemGridTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader()
        );
        $this->loadFixtures([LoadOrderWithNoPriceInLineItem::class]);
    }

    public function testLineItemWithNoPriceAppearsInGrid()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrderWithNoPriceInLineItem::ORDER_WITH_NULL_PRICE);

        $response = $this->client->requestGrid(
            [
                'gridName' => 'order-line-items-grid',
                'order-line-items-grid[order_id]' => $order->getId(),
            ],
            [],
            true
        );
        $result = self::getJsonResponseContent($response, 200);

        $this->assertStringContainsString(
            'N/A',
            $result['data'][0]['price'],
            'Line item price should be displayed as N/A'
        );
    }
}
