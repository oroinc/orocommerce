<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendShoppingListProductUnitsQuantityProvider;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FrontendShoppingListProductUnitsQuantityProviderTest extends TestCase
{
    private ProductShoppingListsDataProvider|MockObject $productShoppingListsDataProvider;

    private FrontendShoppingListProductUnitsQuantityProvider $provider;

    protected function setUp(): void
    {
        $this->productShoppingListsDataProvider = $this->createMock(ProductShoppingListsDataProvider::class);

        $this->provider = new FrontendShoppingListProductUnitsQuantityProvider(
            $this->productShoppingListsDataProvider
        );
    }

    public function testGetByProductWhenNoProduct(): void
    {
        $this->productShoppingListsDataProvider->expects(self::never())
            ->method('getProductsUnitsQuantity');

        self::assertNull($this->provider->getByProduct(null));
    }

    /**
     * @dataProvider getByProductDataProvider
     */
    public function testGetByProduct(int $productId, ?array $expected): void
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
    public function testGetByProductView(int $productId, ?array $expected): void
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
    public function testGetByProducts(int $productId, ?array $expected): void
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

    /**
     * @dataProvider getByProductAndShoppingListDataProvider
     */
    public function testGetByProductAndShoppingList(
        Product|ProductView|null $product,
        int $shoppingListId,
        ?array $shoppingLists,
        ?array $expected
    ): void {
        $this->productShoppingListsDataProvider->expects(self::once())
            ->method('getProductsUnitsQuantity')
            ->willReturn($shoppingLists);

        $shoppingList = (new ShoppingListStub())
            ->setId($shoppingListId);

        self::assertSame($expected, $this->provider->getByProductAndShoppingList($product, $shoppingList));
    }

    public function getByProductAndShoppingListDataProvider(): array
    {
        $productId = 123;

        $product = (new ProductStub())
            ->setId($productId);

        $productView = new ProductView();
        $productView->set('id', $productId);

        $shoppingList1Data = [
            'id' => 1,
            'label' => 'ShoppingList 1',
            'is_current' => true,
            'line_items' => [
                ['id' => 1, 'unit' => 'code1', 'quantity' => 42],
                ['id' => 2, 'unit' => 'code2', 'quantity' => 100],
            ],
        ];
        $shoppingList2Data = [
            'id' => 2,
            'label' => 'ShoppingList 2',
            'is_current' => false,
            'line_items' => [
                ['id' => 3, 'unit' => 'code3', 'quantity' => 30],
            ],
        ];

        return [
            'no prices' => [
                'product' => $product,
                'shoppingListId' => 1,
                'shoppingLists' => [],
                'expected' => null,
            ],
            'single another shopping list' => [
                'product' => $product,
                'shoppingListId' => 123,
                'shoppingLists' => [
                    $productId => [$shoppingList1Data],
                ],
                'expected' => null,
            ],
            'single shopping list' => [
                'product' => $product,
                'shoppingListId' => 1,
                'shoppingLists' => [
                    $productId => [$shoppingList1Data],
                ],
                'expected' => [
                    $shoppingList1Data,
                ],
            ],
            'a few another shopping lists' => [
                'product' => $product,
                'shoppingListId' => 123,
                'shoppingLists' => [
                    $productId => [
                        $shoppingList1Data,
                        $shoppingList2Data
                    ],
                ],
                'expected' => null,
            ],
            'a few shopping lists' => [
                'product' => $product,
                'shoppingListId' => 2,
                'shoppingLists' => [
                    $productId => [
                        $shoppingList1Data,
                        $shoppingList2Data
                    ],
                ],
                'expected' => [
                    $shoppingList2Data,
                ],
            ],
            'a few shopping lists and product view' => [
                'product' => $productView,
                'shoppingListId' => 2,
                'shoppingLists' => [
                    $productId => [
                        $shoppingList1Data,
                        $shoppingList2Data
                    ],
                ],
                'expected' => [
                    $shoppingList2Data,
                ],
            ],
        ];
    }
}
