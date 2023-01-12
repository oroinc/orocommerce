<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

class CheckoutLineItemsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutLineItemsManager|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsManager;

    /** @var CheckoutLineItemsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);

        $this->provider = new CheckoutLineItemsProvider($this->checkoutLineItemsManager);
    }

    private function getCheckoutLineItem(string $sku, string $productUnitCode, float $quantity): CheckoutLineItem
    {
        $item = new CheckoutLineItem();
        $item->setProductSku($sku);
        $item->setProductUnitCode($productUnitCode);
        $item->setQuantity($quantity);

        return $item;
    }

    private function getOrderLineItem(string $sku, string $productUnitCode, float $quantity): OrderLineItem
    {
        $item = new OrderLineItem();
        $item->setProductSku($sku);
        $item->setProductUnitCode($productUnitCode);
        $item->setQuantity($quantity);

        return $item;
    }

    public function testGetProductSkusWithDifferences()
    {
        $lineItems = new ArrayCollection([
            $this->getCheckoutLineItem('SKU1', 'item', 100),
            $this->getCheckoutLineItem('SKU2', 'set', 50)
        ]);
        $sourceLineItems = new ArrayCollection([
            $this->getCheckoutLineItem('SKU1', 'item', 100),
            $this->getCheckoutLineItem('SKU2', 'set', 100),
            $this->getCheckoutLineItem('SKU3', 'box', 100)
        ]);

        $this->assertEquals(
            ['SKU2', 'SKU3'],
            $this->provider->getProductSkusWithDifferences($lineItems, $sourceLineItems)
        );
    }

    public function testGetCheckoutLineItems()
    {
        $checkoutLineItem1 = $this->getCheckoutLineItem('SKU-1', 'item', 1);
        $checkoutLineItem2 = $this->getCheckoutLineItem('SKU-2', 'item', 1);
        $checkoutLineItem3 = $this->getCheckoutLineItem('SKU-2', 'set', 1);

        $orderLineItem1 = $this->getOrderLineItem('SKU-2', 'item', 1);
        $orderLineItem2 = $this->getOrderLineItem('SKU-1', 'item', 1);

        $checkout = $this->createMock(Checkout::class);
        $checkout->expects($this->once())
            ->method('getLineItems')
            ->willReturn(new ArrayCollection([$checkoutLineItem1, $checkoutLineItem2, $checkoutLineItem3]));

        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$orderLineItem1, $orderLineItem2]));

        $result = $this->provider->getCheckoutLineItems($checkout);

        $this->assertCount(2, $result);
        $lineItem1 = $result->first();
        $this->assertEquals('SKU-1', $lineItem1->getProductSku());
        $this->assertEquals('item', $lineItem1->getProductUnitCode());

        $lineItem2 = $result->last();
        $this->assertEquals('SKU-2', $lineItem2->getProductSku());
        $this->assertEquals('item', $lineItem2->getProductUnitCode());
    }
}
