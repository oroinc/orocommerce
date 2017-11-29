<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Provider\MatrixProvider;

class MatrixProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MatrixProvider
     */
    protected $testedProvider;

    protected function setUp()
    {
        $this->testedProvider = new MatrixProvider();
    }

    public function testHasEmptyMatrixTrue()
    {
        $shoppingList = new ShoppingList();
        $product = new Product();
        $configurableProduct = (new Product())->setType(Product::TYPE_CONFIGURABLE);

        $shoppingList
            ->addLineItem(
                (new LineItem())->setProduct($product)
            )
            ->addLineItem(
                (new LineItem())->setProduct($product)
            )
            ->addLineItem(
                (new LineItem())->setProduct($configurableProduct)
            );

        self::assertTrue($this->testedProvider->hasEmptyMatrix($shoppingList));
    }

    public function testHasEmptyMatrixFalse()
    {
        $shoppingList = new ShoppingList();
        $product = new Product();

        $shoppingList
            ->addLineItem(
                (new LineItem())->setProduct($product)
            )
            ->addLineItem(
                (new LineItem())->setProduct($product)
            )
            ->addLineItem(
                (new LineItem())->setProduct($product)
            );

        self::assertFalse($this->testedProvider->hasEmptyMatrix($shoppingList));
    }
}
