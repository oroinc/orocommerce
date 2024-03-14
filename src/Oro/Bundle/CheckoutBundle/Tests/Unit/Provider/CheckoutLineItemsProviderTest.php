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

    private function getCheckoutLineItem(
        string $sku,
        string $productUnitCode,
        float $quantity,
        string $checksum
    ): CheckoutLineItem {
        $lineItem = new CheckoutLineItem();
        $lineItem->setProductSku($sku);
        $lineItem->setProductUnitCode($productUnitCode);
        $lineItem->setQuantity($quantity);
        $lineItem->setChecksum($checksum);

        return $lineItem;
    }

    private function getOrderLineItem(
        string $sku,
        string $productUnitCode,
        float $quantity,
        string $checksum
    ): OrderLineItem {
        $lineItem = new OrderLineItem();
        $lineItem->setProductSku($sku);
        $lineItem->setProductUnitCode($productUnitCode);
        $lineItem->setQuantity($quantity);
        $lineItem->setChecksum($checksum);

        return $lineItem;
    }

    public function testGetProductSkusWithDifferences(): void
    {
        $lineItems = new ArrayCollection([
            $this->getCheckoutLineItem('SKU1', 'item', 100, ''),
            $this->getCheckoutLineItem('SKU2', 'set', 50, ''),
            $this->getCheckoutLineItem('SKU2', 'set', 50, 'sample_checksum1'),
        ]);
        $sourceLineItems = new ArrayCollection([
            $this->getCheckoutLineItem('SKU1', 'item', 100, ''),
            $this->getCheckoutLineItem('SKU2', 'set', 100, ''),
            $this->getCheckoutLineItem('SKU2', 'set', 50, 'sample_checksum2'),
            $this->getCheckoutLineItem('SKU3', 'box', 100, ''),
        ]);

        self::assertEquals(
            ['SKU2', 'SKU2', 'SKU3'],
            $this->provider->getProductSkusWithDifferences($lineItems, $sourceLineItems)
        );
    }

    public function testGetCheckoutLineItems(): void
    {
        $checkoutLineItem1 = $this->getCheckoutLineItem('SKU-1', 'item', 1, '');
        $checkoutLineItem2 = $this->getCheckoutLineItem('SKU-2', 'item', 1, '');
        $checkoutLineItem3 = $this->getCheckoutLineItem('SKU-2', 'set', 1, '');
        $checkoutLineItem4 = $this->getCheckoutLineItem('SKU-2', 'set', 1, 'sample_checksum4');

        $orderLineItem1 = $this->getOrderLineItem('SKU-2', 'item', 1, '');
        $orderLineItem2 = $this->getOrderLineItem('SKU-1', 'item', 1, '');
        $orderLineItem4 = $this->getOrderLineItem('SKU-2', 'set', 1, 'sample_checksum4');

        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getLineItems')
            ->willReturn(
                new ArrayCollection([$checkoutLineItem1, $checkoutLineItem2, $checkoutLineItem3, $checkoutLineItem4])
            );

        $this->checkoutLineItemsManager->expects(self::once())
            ->method('getData')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$orderLineItem1, $orderLineItem2, $orderLineItem4]));


        self::assertEquals(
            new ArrayCollection([0 => $checkoutLineItem1, 1 => $checkoutLineItem2, 3 => $checkoutLineItem4]),
            $this->provider->getCheckoutLineItems($checkout)
        );
    }
}
