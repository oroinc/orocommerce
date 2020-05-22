<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

class CheckoutLineItemsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutLineItemsProvider */
    protected $provider;

    protected function setUp(): void
    {
        $this->provider = new CheckoutLineItemsProvider();
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
     * @return ProductLineItemInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getCheckoutLineItem($productSku, $productUnitCode, $quantity)
    {
        return $this->getLineItem(ProductLineItemInterface::class, $productSku, $productUnitCode, $quantity);
    }

    /**
     * @param string $productSku
     * @param string $productUnitCode
     * @param int $quantity
     * @return ProductLineItemInterface|\PHPUnit\Framework\MockObject\MockObject
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
     * @return \PHPUnit\Framework\MockObject\MockObject
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
}
