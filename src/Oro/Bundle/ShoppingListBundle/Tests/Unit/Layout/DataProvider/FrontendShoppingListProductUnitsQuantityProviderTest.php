<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendShoppingListProductUnitsQuantityProvider;

class FrontendShoppingListProductUnitsQuantityProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ProductShoppingListsDataProvider */
    protected $productShoppingListsDataProvider;

    /** @var FrontendShoppingListProductUnitsQuantityProvider */
    protected $provider;

    protected function setUp()
    {
        $this->productShoppingListsDataProvider = $this
            ->getMockBuilder('Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new FrontendShoppingListProductUnitsQuantityProvider(
            $this->productShoppingListsDataProvider
        );
    }

    protected function tearDown()
    {
        unset(
            $this->provider,
            $this->productShoppingListsDataProvider
        );
    }

    /**
     * @dataProvider getByProductDataProvider
     *
     * @param Product|null $product
     * @param array|null $expected
     */
    public function testGetByProduct(Product $product = null, array $expected = null)
    {
        $this->productShoppingListsDataProvider
            ->expects($this->any())
            ->method('getProductsUnitsQuantity')
            ->willReturn($expected ? [$expected] : $expected);

        $this->assertEquals($expected, $this->provider->getByProduct($product));
    }

    /**
     * @return array
     */
    public function getByProductDataProvider()
    {
        $product = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Product')
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->any())->method('getId')->willReturn(0);
        return [
            'no_product' => [
                'product' => null,
            ],
            'no_prices' => [
                'product' => new Product()
            ],
            'single_shopping_list' => [
                'product' => $product,
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
                'product' => $product,
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
