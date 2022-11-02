<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendShoppingListProductUnitsQuantityProvider;

class FrontendShoppingListProductUnitsQuantityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductShoppingListsDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productShoppingListsDataProvider;

    /** @var FrontendShoppingListProductUnitsQuantityProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->productShoppingListsDataProvider = $this->createMock(ProductShoppingListsDataProvider::class);

        $this->provider = new FrontendShoppingListProductUnitsQuantityProvider(
            $this->productShoppingListsDataProvider
        );
    }

    public function testGetByProductWhenNoProduct()
    {
        $this->productShoppingListsDataProvider->expects(self::never())
            ->method('getProductsUnitsQuantity');

        self::assertNull($this->provider->getByProduct(null));
    }

    /**
     * @dataProvider getByProductDataProvider
     */
    public function testGetByProduct(int $productId, ?array $expected)
    {
        $product = $this->createMock(Product::class);
        $product->expects(self::any())
            ->method('getId')
            ->willReturn($productId);

        $shoppingLists = [];
        if (null !== $expected) {
            $shoppingLists = [$productId => $expected];
        }

        $this->productShoppingListsDataProvider->expects(self::once())
            ->method('getProductsUnitsQuantity')
            ->willReturn($shoppingLists);

        self::assertSame($expected, $this->provider->getByProduct($product));
    }

    /**
     * @dataProvider getByProductDataProvider
     */
    public function testGetByProductView(int $productId, ?array $expected)
    {
        $product = new ProductView();
        $product->set('id', $productId);

        $shoppingLists = [];
        if (null !== $expected) {
            $shoppingLists = [$productId => $expected];
        }

        $this->productShoppingListsDataProvider->expects(self::once())
            ->method('getProductsUnitsQuantity')
            ->willReturn($shoppingLists);

        self::assertSame($expected, $this->provider->getByProduct($product));
    }

    /**
     * @dataProvider getByProductDataProvider
     */
    public function testGetByProducts(int $productId, ?array $expected)
    {
        $shoppingLists = [];
        if (null === $expected) {
            $expected = [];
        } else {
            $shoppingLists = [$productId => $expected];
            $expected = [$productId => $expected];
        }

        $this->productShoppingListsDataProvider->expects(self::any())
            ->method('getProductsUnitsQuantity')
            ->willReturn($shoppingLists);

        $productView = new ProductView();
        $productView->set('id', $productId);

        self::assertSame($expected, $this->provider->getByProducts([$productView]));
    }

    public function getByProductDataProvider(): array
    {
        return [
            'no_prices' => [
                'productId' => 123,
                'expected' => null
            ],
            'single_shopping_list' => [
                'productId' => 123,
                'expected' => [
                    [
                        'id' => 1,
                        'label' => 'ShoppingList 1',
                        'is_current' => true,
                        'line_items' => [
                            ['id' => 1, 'unit' => 'code1', 'quantity' => 42],
                            ['id' => 2, 'unit' => 'code2', 'quantity' => 100],
                        ]
                    ]
                ]
            ],
            'a_few_shopping_lists' => [
                'productId' => 123,
                'expected' => [
                    [
                        'id' => 1,
                        'label' => 'ShoppingList 1',
                        'is_current' => true,
                        'line_items' => [
                            ['id' => 1, 'unit' => 'code1', 'quantity' => 42],
                            ['id' => 2,'unit' => 'code2', 'quantity' => 100],
                        ]
                    ],
                    [
                        'id' => 2,
                        'label' => 'ShoppingList 2',
                        'is_current' => false,
                        'line_items' => [
                            ['id' => 3, 'unit' => 'code3', 'quantity' => 30],
                        ]
                    ]
                ]
            ],
        ];
    }
}
