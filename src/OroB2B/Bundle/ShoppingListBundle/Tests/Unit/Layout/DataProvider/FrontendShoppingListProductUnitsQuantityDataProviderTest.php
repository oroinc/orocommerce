<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;
use OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendShoppingListProductUnitsQuantityDataProvider;

class FrontendShoppingListProductUnitsQuantityDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ProductShoppingListsDataProvider */
    protected $productShoppingListsDataProvider;

    /** @var FrontendShoppingListProductUnitsQuantityDataProvider */
    protected $provider;

    protected function setUp()
    {
        $this->productShoppingListsDataProvider = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new FrontendShoppingListProductUnitsQuantityDataProvider(
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
     * @dataProvider getDataDataProvider
     *
     * @param Product|null $product
     * @param array|null $expected
     */
    public function testGetData(Product $product = null, array $expected = null)
    {
        $context = new LayoutContext();
        $context->data()->set('product', null, $product);

        $this->productShoppingListsDataProvider
            ->expects($this->any())
            ->method('getProductUnitsQuantity')
            ->willReturn($expected);

        $this->assertEquals($expected, $this->provider->getData($context));
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        return [
            'no_product' => [
                'product' => null,
            ],
            'no_prices' => [
                'product' => new Product(),
                'expected' => []
            ],
            'single_shopping_list' => [
                'product' => new Product(),
                'expected' => [
                    [
                        'shopping_list_id' => 1,
                        'shopping_list_label' => 'ShoppingList 1',
                        'is_current' => true,
                        'line_items' => [
                            ['line_item_id' => 1, 'unit' => 'code1', 'quantity' => 42],
                            ['line_item_id' => 2, 'unit' => 'code2', 'quantity' => 100],
                        ]
                    ]
                ]
            ],
            'a_few_shopping_lists' => [
                'product' => new Product(),
                'expected' => [
                    [
                        'shopping_list_id' => 1,
                        'shopping_list_label' => 'ShoppingList 1',
                        'is_current' => true,
                        'line_items' => [
                            ['line_item_id' => 1, 'unit' => 'code1', 'quantity' => 42],
                            ['line_item_id' => 2,'unit' => 'code2', 'quantity' => 100],
                        ]
                    ],
                    [
                        'shopping_list_id' => 2,
                        'shopping_list_label' => 'ShoppingList 2',
                        'is_current' => false,
                        'line_items' => [
                            ['line_item_id' => 3, 'unit' => 'code3', 'quantity' => 30],
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Undefined data item index: product.
     */
    public function testGetDataWithEmptyContext()
    {
        $this->provider->getData(new LayoutContext());
    }
}
