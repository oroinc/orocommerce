<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\LineItemChecksumGenerator;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\GeneralLineItemChecksumGenerator;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use PHPUnit\Framework\TestCase;

class GeneralLineItemChecksumGeneratorTest extends TestCase
{
    private GeneralLineItemChecksumGenerator $generator;

    #[\Override]
    protected function setUp(): void
    {
        $this->generator = new GeneralLineItemChecksumGenerator();
    }

    public function testGetChecksumWhenNoProductNoProductUnit(): void
    {
        self::assertNull($this->generator->getChecksum(new ProductLineItemStub(42)));
    }

    public function testGetChecksumWhenNoProduct(): void
    {
        $unitItem = (new ProductUnit())->setCode('item');
        $lineItem = (new ProductLineItemStub(42))->setUnit($unitItem);

        self::assertNull($this->generator->getChecksum($lineItem));
    }

    public function testGetChecksumWhenNoProductUnit(): void
    {
        $product = (new ProductStub())
            ->setId(42)
            ->setType(Product::TYPE_KIT);
        $lineItem = (new ProductLineItemStub(42))
            ->setProduct($product);

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
