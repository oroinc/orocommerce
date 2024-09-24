<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\PricingBundle\Provider\FormattedProductPriceProvider;
use Oro\Bundle\ProductBundle\Autocomplete\ProductVisibilityLimitedWithPricesSearchHandler;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductVisibilityLimitedWithPricesSearchHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var SearchHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $baseSearchHandler;

    /** @var FormattedProductPriceProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $formattedProductPriceProvider;

    /** @var ProductVisibilityLimitedWithPricesSearchHandler */
    private $searchHandler;

    #[\Override]
    protected function setUp(): void
    {
        $this->baseSearchHandler = $this->createMock(SearchHandlerInterface::class);
        $this->formattedProductPriceProvider = $this->createMock(FormattedProductPriceProvider::class);

        $this->searchHandler = new ProductVisibilityLimitedWithPricesSearchHandler(
            $this->baseSearchHandler,
            $this->formattedProductPriceProvider
        );
    }

    public function testConvertItem(): void
    {
        $item = ['key' => 'val'];
        $convertedItem = ['key1' => 'val1'];

        $this->baseSearchHandler->expects(self::once())
            ->method('convertItem')
            ->with($item)
            ->willReturn($convertedItem);

        self::assertSame($convertedItem, $this->searchHandler->convertItem($item));
    }

    public function testGetProperties(): void
    {
        $properties = [];
        $this->baseSearchHandler->expects(self::once())
            ->method('getProperties')
            ->willReturn($properties);

        self::assertSame($properties, $this->searchHandler->getProperties());
    }

    public function testGetEntityName(): void
    {
        $this->baseSearchHandler->expects(self::once())
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

        $this->baseSearchHandler->expects(self::once())
            ->method('search')
            ->with($query, $page, $perPage, $isId)
            ->willReturn($result);

        $this->formattedProductPriceProvider->expects(self::never())
            ->method('getFormattedProductPrices');

        self::assertSame($result, $this->searchHandler->search($query, $page, $perPage, $isId));
    }

    /**
     * @dataProvider searchDataProvider
     */
    public function testSearch(array $result, array $prices, array $expected): void
    {
        $query = 'test';
        $page = 1;
        $perPage = 1;
        $isId = false;

        $this->baseSearchHandler->expects(self::once())
            ->method('search')
            ->with($query, $page, $perPage, $isId)
            ->willReturn($result);

        $this->formattedProductPriceProvider->expects(self::once())
            ->method('getFormattedProductPrices')
            ->with(array_column($result['results'], 'id'))
            ->willReturn($prices);

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
                    2 => [
                        'prices' => ['items' => ['price' => 1.0]],
                        'units' => ['items' => 1]
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
                [],
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
                        ['id' => 3, 'sku' => 'Sku3', 'name' => 'test 3'],
                        ['id' => 4, 'sku' => 'Sku4', 'name' => 'test 4'],
                        ['id' => 5, 'sku' => 'Sku5', 'name' => 'test 5']
                    ]
                ],
                [
                    1 => [
                        'prices' => ['items' => ['price' => 1.0]],
                        'units' => ['items' => 1]
                    ],
                    2 => [
                        'prices' => ['items' => ['price' => 10.0]],
                        'units' => ['items' => 1]
                    ],
                    3 => [
                        'prices' => ['items' => ['price' => 20.0]],
                        'units' => ['items' => 1]
                    ],
                    5 => [
                        'prices' => ['items' => ['price' => 30.0]],
                        'units' => ['items' => 1]
                    ]
                ],
                [
                    'more' => false,
                    'results' => [
                        [
                            'id' => 2,
                            'sku' => 'SkuАбв',
                            'name' => 'test name',
                            'prices' => ['items' => ['price' => 10.0]],
                            'units' => ['items' => 1]
                        ],
                        [
                            'id' => 3,
                            'sku' => 'Sku3',
                            'name' => 'test 3',
                            'prices' => ['items' => ['price' => 20.0]],
                            'units' => ['items' => 1]
                        ],
                        [
                            'id' => 5,
                            'sku' => 'Sku5',
                            'name' => 'test 5',
                            'prices' => ['items' => ['price' => 30.0]],
                            'units' => ['items' => 1]
                        ]
                    ]
                ]
            ]
        ];
    }
}
