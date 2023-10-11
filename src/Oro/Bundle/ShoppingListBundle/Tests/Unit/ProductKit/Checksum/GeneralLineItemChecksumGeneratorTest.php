<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\ProductKit\Checksum;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\ProductKit\Checksum\GeneralLineItemChecksumGenerator;
use PHPUnit\Framework\TestCase;

class GeneralLineItemChecksumGeneratorTest extends TestCase
{
    private GeneralLineItemChecksumGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new GeneralLineItemChecksumGenerator();
    }

    public function testGetChecksumWhenNoProduct(): void
    {
        self::assertNull($this->generator->getChecksum(new LineItem()));
    }

    public function testGetChecksumWhenIsKit(): void
    {
        $product = (new ProductStub())
            ->setId(42)
            ->setType(Product::TYPE_KIT);
        $productUnit = (new ProductUnit())->setCode('item');
        $lineItem = (new LineItem())
            ->setProduct($product)
            ->setUnit($productUnit);

        self::assertNull($this->generator->getChecksum($lineItem));
    }

    public function testGetChecksum(): void
    {
        $shoppingList = (new ShoppingList());
        $product = (new ProductStub())
            ->setId(43);
        $productUnit = (new ProductUnit())->setCode('item');
        $lineItem = (new LineItem())
            ->setProduct($product)
            ->setUnit($productUnit)
            ->setShoppingList($shoppingList);

        self::assertEquals('43|item', $this->generator->getChecksum($lineItem));
    }
}
