<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\ProductBundle\Autocomplete\ProductVisibilityLimitedWithPricesSearchHandler;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductVisibilityLimitedWithPricesSearchHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var SearchHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productWithPricesSearchHandler;

    /** @var SearchHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productVisibilityLimitedSearchHandler;

    /** @var ProductVisibilityLimitedWithPricesSearchHandler */
    private $searchHandler;

    protected function setUp(): void
    {
        $this->productWithPricesSearchHandler = $this->createMock(SearchHandlerInterface::class);
        $this->productVisibilityLimitedSearchHandler = $this->createMock(SearchHandlerInterface::class);

        $this->searchHandler = new ProductVisibilityLimitedWithPricesSearchHandler(
            $this->productWithPricesSearchHandler,
            $this->productVisibilityLimitedSearchHandler
        );
    }

    public function testConvertItem(): void
    {
        $item = ['key' => 'val'];
        $convertedItem = ['key1' => 'val1'];

        $this->productWithPricesSearchHandler->expects(self::once())
            ->method('convertItem')
            ->with($item)
            ->willReturn($convertedItem);

        self::assertSame($convertedItem, $this->searchHandler->convertItem($item));
    }

    public function testGetProperties(): void
    {
        $properties = [];
        $this->productWithPricesSearchHandler->expects(self::once())
            ->method('getProperties')
            ->willReturn($properties);

        self::assertSame($properties, $this->searchHandler->getProperties());
    }

    public function testGetEntityName(): void
    {
        $this->productWithPricesSearchHandler->expects(self::once())
            ->method('getEntityName')
            ->willReturn(Product::class);

        self::assertSame(Product::class, $this->searchHandler->getEntityName());
    }

    public function testSearchNoProductsFound(): void
    {
        $query = 'test';
        $page = 1;
        $perPage = 1;
        $isId = false;
        $result = [
            'more' => false,
            'results' => []
        ];

        $this->productVisibilityLimitedSearchHandler->expects(self::once())
            ->method('search')
            ->with($query, $page, $perPage, $isId)
            ->willReturn($result);

        $this->productWithPricesSearchHandler->expects(self::never())
            ->method('search');

        self::assertSame($result, $this->searchHandler->search($query, $page, $perPage, $isId));
    }

    /**
     * @dataProvider searchDataProvider
     */
    public function testSearch(array $result, array $pricesResult, array $expected): void
    {
        $query = 'test';
        $page = 1;
        $perPage = 1;
        $isId = false;

        $this->productVisibilityLimitedSearchHandler->expects(self::once())
            ->method('search')
            ->with($query, $page, $perPage, $isId)
            ->willReturn($result);

        $this->productWithPricesSearchHandler->expects(self::once())
            ->method('search')
            ->with(implode(',', array_column($result['results'], 'id')), $page, $perPage, true)
            ->willReturn($pricesResult);

        self::assertSame($expected, $this->searchHandler->search($query, $page, $perPage, $isId));
    }

    public function searchDataProvider(): array
    {
        return [
            'pricing results does not match' => [
                [
                    'more' => false,
                    'results' => [
                        ['id' => 1, 'sku' => 'test', 'name' => 'test name']
                    ]
                ],
                [
                    'more' => false,
                    'results' => [
                        [
                            'id' => 2,
                            'sku' => 'test2',
                            'name' => 'test name',
                            'prices' => [['value' => 10, 'unit' => 'item']]
                        ]
                    ]
                ],
                [
                    'more' => false,
                    'results' => []
                ]
            ],
            'no pricing results' => [
                [
                    'more' => false,
                    'results' => [
                        ['id' => 1, 'sku' => 'test', 'name' => 'test name']
                    ]
                ],
                [
                    'more' => false,
                    'results' => []
                ],
                [
                    'more' => false,
                    'results' => []
                ]
            ],
            'pricing results match' => [
                [
                    'more' => false,
                    'results' => [
                        ['id' => 2, 'sku' => 'SkuАбв', 'name' => 'test name'],
                        ['id' => 3, 'sku' => 'Sku3', 'name' => 'test 3']
                    ]
                ],
                [
                    'more' => false,
                    'results' => [
                        [
                            'id' => 1,
                            'sku' => 'Sku1',
                            'name' => 'Sku1',
                            'prices' => [['value' => 1, 'unit' => 'item']]
                        ],
                        [
                            'id' => 2,
                            'sku' => 'SkuАбв',
                            'name' => 'test name',
                            'prices' => [['value' => 10, 'unit' => 'item']]
                        ],
                        [
                            'id' => 3,
                            'sku' => 'Sku3',
                            'name' => 'test 3',
                            'prices' => [['value' => 20, 'unit' => 'item']]
                        ]
                    ]
                ],
                [
                    'more' => false,
                    'results' => [
                        [
                            'id' => 2,
                            'sku' => 'SkuАбв',
                            'name' => 'test name',
                            'prices' => [['value' => 10, 'unit' => 'item']]
                        ],
                        [
                            'id' => 3,
                            'sku' => 'Sku3',
                            'name' => 'test 3',
                            'prices' => [['value' => 20, 'unit' => 'item']]
                        ]
                    ]
                ]
            ],
        ];
    }
}
