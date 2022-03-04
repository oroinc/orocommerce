<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ComponentProcessor;

use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorFilter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class ComponentProcessorFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productRepository;

    /** @var ComponentProcessorFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);

        $this->filter = new ComponentProcessorFilter($this->productRepository);
    }

    public function testFilterDataOnEmptyInput()
    {
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => []
        ];
        $dataParameters = [];

        $result = $this->filter->filterData($data, $dataParameters);
        $this->assertEquals($result, $data);
    }

    public function testFilterData()
    {
        $skus = ['visibleSku1', 'invisibleSku1', 'visibleSku2Абв'];
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                ['productSku' => $skus[0]],
                ['productSku' => $skus[1]],
                ['productSku' => $skus[2]],
            ],
        ];
        $dataParameters = [];

        $searchQuery = $this->getMockBuilder(SearchQueryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResult'])
            ->addMethods(['toArray'])
            ->getMockForAbstractClass();
        $searchQuery->expects($this->once())
            ->method('getResult')
            ->willReturnSelf();
        $searchQuery->expects($this->once())
            ->method('toArray')
            ->willReturnCallback(function () use ($skus) {
                $filteredSkus = [];
                foreach ($skus as $index => $sku) {
                    if (!str_contains($sku, 'invisibleSku')) {
                        $filteredSkus[] = new Item(
                            Product::class,
                            $index,
                            null,
                            [
                                'sku'           => $sku,
                                'sku_uppercase' => mb_strtoupper($sku)
                            ]
                        );
                    }
                }
                return $filteredSkus;
            });

        $this->productRepository->expects($this->once())
            ->method('getFilterSkuQuery')
            ->with(array_map('mb_strtoupper', $skus))
            ->willReturn($searchQuery);

        $filteredData = $this->filter->filterData($data, $dataParameters);

        $this->assertIsArray($filteredData);
        $this->assertCount(2, $filteredData[ProductDataStorage::ENTITY_ITEMS_DATA_KEY]);
        $this->assertEquals(
            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][0],
            $filteredData[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][0]
        );
        $this->assertEquals(
            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][2],
            $filteredData[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][1]
        );
    }
}
