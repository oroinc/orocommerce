<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Search;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\IndexerQuery;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductRepositoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var QueryFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $queryFactory;

    /** @var AbstractSearchMappingProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $mappingProvider;

    /** @var ProductRepository */
    protected $repository;

    protected function setUp()
    {
        $this->queryFactory = $this->createMock(QueryFactoryInterface::class);
        $this->mappingProvider = $this->createMock(AbstractSearchMappingProvider::class);

        $this->repository = new ProductRepository($this->queryFactory, $this->mappingProvider);
    }

    /**
     * @dataProvider getCategoryCountsDataProvider
     *
     * @param Category $category
     * @param array $expected
     */
    public function testGetCategoryCountsByCategory(Category $category, array $expected)
    {
        $query = new Query();

        /** @var Indexer|\PHPUnit_Framework_MockObject_MockObject $indexer */
        $indexer = $this->createMock(Indexer::class);
        $indexer->expects($this->once())
            ->method('query')
            ->with($query)
            ->willReturn(
                new Result(
                    $query,
                    [],
                    0,
                    [
                        'categoryCounts' => [
                            '1' => 0,
                            '1_2' => 3,
                            '1_2_3' => 5,
                            '1_2_4' => 5,
                            '1_2_4_5' => 7,
                            '1_6_7' => 10,
                            '1_6_7_8_9' => 20,
                            '1_10_11' => 7,
                            '1_10_12' => 5,
                        ]
                    ]
                )
            );

        $this->queryFactory->expects($this->once())
            ->method('create')
            ->willReturn(new IndexerQuery($indexer, $query));

        $this->assertEquals($expected, $this->repository->getCategoryCountsByCategory($category));
    }

    /**
     * @return array
     */
    public function getCategoryCountsDataProvider()
    {
        return [
            'with root category' => [
                'category' => $this->getCategory(1, '1'),
                'expected' => [
                    '2' => 20,
                    '6' => 30,
                    '10' => 12,
                ]
            ],
            'with sub category' => [
                'category' => $this->getCategory(2, '1_2'),
                'expected' => [
                    '3' => 5,
                    '4' => 12,
                ]
            ],
            'with unknown category' => [
                'category' => $this->getCategory(100, '100'),
                'expected' => []
            ]
        ];
    }

    /**
     * @param int $id
     * @param string $path
     * @return Category
     */
    protected function getCategory($id, $path)
    {
        return $this->getEntity(Category::class, ['id' => $id, 'materializedPath' => $path]);
    }
}
