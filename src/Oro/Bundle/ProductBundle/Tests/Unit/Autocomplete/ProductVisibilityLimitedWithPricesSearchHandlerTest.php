<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\ProductBundle\Autocomplete\ProductVisibilityLimitedWithPricesSearchHandler;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductVisibilityLimitedWithPricesSearchHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SearchHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productWithPricesSearchHandler;

    /**
     * @var SearchHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productVisibilityLimitedSearchHandler;

    /**
     * @var SearchHandlerInterface
     */
    private $searchHandler;

    protected function setUp()
    {
        $this->productWithPricesSearchHandler = $this->createMock(SearchHandlerInterface::class);
        $this->productVisibilityLimitedSearchHandler = $this->createMock(SearchHandlerInterface::class);

        $this->searchHandler = new ProductVisibilityLimitedWithPricesSearchHandler(
            $this->productWithPricesSearchHandler,
            $this->productVisibilityLimitedSearchHandler
        );
    }

    public function testConvertItem()
    {
        $item = [];
        $this->productWithPricesSearchHandler->expects($this->once())
            ->method('convertItem')
            ->willReturn($item);

        $this->assertSame($item, $this->searchHandler->convertItem($item));
    }

    public function testGetProperties()
    {
        $properties = [];
        $this->productWithPricesSearchHandler->expects($this->once())
            ->method('getProperties')
            ->willReturn($properties);

        $this->assertSame($properties, $this->searchHandler->getProperties());
    }

    public function testGetEntityName()
    {
        $this->productWithPricesSearchHandler->expects($this->once())
            ->method('getEntityName')
            ->willReturn(Product::class);

        $this->assertSame(Product::class, $this->searchHandler->getEntityName());
    }

    public function testSearchNoProductsFound()
    {
        $query = 'test';
        $page = 1;
        $perPage = 1;
        $isId = false;
        $result = [
            'more' => false,
            'results' => []
        ];

        $this->productVisibilityLimitedSearchHandler->expects($this->once())
            ->method('search')
            ->with($query, $page, $perPage, $isId)
            ->willReturn($result);

        $this->productWithPricesSearchHandler->expects($this->never())
            ->method('search');

        $this->assertSame($result, $this->searchHandler->search($query, $page, $perPage, $isId));
    }

    /**
     * @dataProvider searchDataProvider
     *
     * @param array $resultData
     * @param array $priceResultData
     * @param array $expectedResults
     */
    public function testSearch(array $resultData, array $priceResultData, array $expectedResults)
    {
        $query = 'test';
        $page = 1;
        $perPage = 1;
        $isId = false;

        $this->productVisibilityLimitedSearchHandler->expects($this->once())
            ->method('search')
            ->with($query, $page, $perPage, $isId)
            ->willReturn($resultData);

        $this->productWithPricesSearchHandler->expects($this->once())
            ->method('search')
            ->with($query, $page, $perPage, $isId)
            ->willReturn($priceResultData);

        $this->assertEquals($expectedResults, $this->searchHandler->search($query, $page, $perPage, $isId));
    }

    /**
     * @return array
     */
    public function searchDataProvider()
    {
        return [
            'pricing results does not match' => [
                'resultsData' => [
                    'results' => [['id' => 1, 'name' => 'test name']]
                ],
                'priceResultsData' => [
                    'results' => [['id' => 2, 'name' => 'test name', 'prices' => [['value' => 10]]]]
                ],
                'expectedResults' => [
                    'results' => []
                ]
            ],
            'no pricing results' => [
                'resultsData' => [
                    'results' => [['id' => 1, 'name' => 'test name']]
                ],
                'priceResultsData' => [
                    'results' => []
                ],
                'expectedResults' => [
                    'results' => []
                ]
            ],
            'pricing results match' => [
                'resultsData' => [
                    'results' => [['id' => 1, 'name' => 'test name']]
                ],
                'priceResultsData' => [
                    'results' => [['id' => 1, 'name' => 'test name', 'prices' => [['value' => 10]]]]
                ],
                'expectedResults' => [
                    'results' => [['id' => 1, 'name' => 'test name', 'prices' => [['value' => 10]]]]
                ]
            ],
            'not all pricing results is match' => [
                'resultsData' => [
                    'results' => [
                        ['id' => 1, 'name' => 'test name 1'],
                        ['id' => 2, 'name' => 'test name 2'],
                    ]
                ],
                'priceResultsData' => [
                    'results' => [
                        ['id' => 2, 'name' => 'test name 2', 'prices' => [['value' => 10]]],
                        ['id' => 3, 'name' => 'test name 3', 'prices' => [['value' => 20]]],
                    ]
                ],
                'expectedResults' => [
                    'results' => [['id' => 2, 'name' => 'test name 2', 'prices' => [['value' => 10]]]]
                ]
            ],
            'pricing results match and used price results data' => [
                'resultsData' => [
                    'results' => [['id' => 1, 'name' => 'test name extra']]
                ],
                'priceResultsData' => [
                    'results' => [['id' => 1, 'name' => 'test name', 'prices' => [['value' => 10]]]]
                ],
                'expectedResults' => [
                    'results' => [['id' => 1, 'name' => 'test name', 'prices' => [['value' => 10]]]]
                ]
            ],
        ];
    }
}
