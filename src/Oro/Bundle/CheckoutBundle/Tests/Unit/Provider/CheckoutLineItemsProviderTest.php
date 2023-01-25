<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckoutLineItemsProviderTest extends TestCase
{
    use EntityTrait;

    private CheckoutLineItemsManager|MockObject $checkoutLineItemsManager;
    private CheckoutLineItemsProvider $provider;

    protected function setUp(): void
    {
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);
        $this->provider = new CheckoutLineItemsProvider();
        $this->provider->setCheckoutLineItemsManager($this->checkoutLineItemsManager);
    }

    public function testGetProductSkusWithDifferences()
    {
        $lineItems = new ArrayCollection(
            [
                $this->getCheckoutLineItem('SKU1', 'item', 100),
                $this->getCheckoutLineItem('SKU2', 'set', 50),
            ]
        );
        $sourceLineItems = new ArrayCollection(
            [
                $this->getCheckoutLineItem('SKU1', 'item', 100),
                $this->getCheckoutLineItem('SKU2', 'set', 100),
                $this->getCheckoutLineItem('SKU3', 'box', 100),
            ]
        );

        $this->assertEquals(
            ['SKU2', 'SKU3'],
            $this->provider->getProductSkusWithDifferences($lineItems, $sourceLineItems)
        );
    }

    /**
     * @param string $productSku
     * @param string $productUnitCode
     * @param int $quantity
     * @return ProductLineItemInterface|MockObject
     */
    protected function getCheckoutLineItem($productSku, $productUnitCode, $quantity)
    {
        return $this->getLineItem(ProductLineItemInterface::class, $productSku, $productUnitCode, $quantity);
    }

    /**
     * @param string $productSku
     * @param string $productUnitCode
     * @param int $quantity
     * @return ProductLineItemInterface|MockObject
     */
    protected function getProductLineItem($productSku, $productUnitCode, $quantity)
    {
        return $this->getLineItem(ProductLineItemInterface::class, $productSku, $productUnitCode, $quantity);
    }

    /**
     * @param string $class
     * @param string $productSku
     * @param string $productUnitCode
     * @param int $quantity
     * @return MockObject
     */
    protected function getLineItem($class, $productSku, $productUnitCode, $quantity)
    {
        $item = $this->createMock($class);
        $item->expects($this->any())
            ->method('getProductSku')
            ->willReturn($productSku);
        $item->expects($this->any())
            ->method('getProductUnitCode')
            ->willReturn($productUnitCode);
        $item->expects($this->any())
            ->method('getQuantity')
            ->willReturn($quantity);

        return $item;
    }

    public function testGetCheckoutLineItems()
    {
        $checkoutLineItem1 = $this->getLineItem(CheckoutLineItem::class, 'SKU-1', 'item', 1);
        $checkoutLineItem2 = $this->getLineItem(CheckoutLineItem::class, 'SKU-2', 'item', 1);
        $checkoutLineItem3 = $this->getLineItem(CheckoutLineItem::class, 'SKU-2', 'set', 1);

        $orderLineItem1 = $this->getLineItem(OrderLineItem::class, 'SKU-2', 'item', 1);
        $orderLineItem2 = $this->getLineItem(OrderLineItem::class, 'SKU-1', 'item', 1);

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
