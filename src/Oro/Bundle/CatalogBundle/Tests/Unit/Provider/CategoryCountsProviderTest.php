<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Provider\CategoryCountsProvider;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class CategoryCountsProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ProductRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $productSearchRepository;

    /** @var CategoryCountsProvider */
    protected $provider;

    protected function setUp()
    {
        $this->productSearchRepository = $this->createMock(ProductRepository::class);

        $this->provider = new CategoryCountsProvider($this->productSearchRepository);
    }

    /**
     * @dataProvider getCategoryCountsDataProvider
     *
     * @param array $expected
     * @param Category|null $category
     */
    public function testGetCategoryCounts(array $expected, Category $category = null)
    {
        /** @var SearchQueryInterface $searchQuery */
        $searchQuery = $this->createMock(SearchQueryInterface::class);

        $this->productSearchRepository->expects($this->once())
            ->method('getCategoryCounts')
            ->with($searchQuery)
            ->willReturn(
                [
                    '1' => 0,
                    '1_2' => 3,
                    '1_2_3' => 5,
                    '1_2_4' => 5,
                    '1_2_4_5' => 7,
                    '6_7' => 10,
                    '6_7_8_9' => 20,
                    '10_11' => 7,
                    '10_12' => 5,
                ]
            );

        $this->assertEquals($expected, $this->provider->getCategoryCounts($searchQuery, $category));
    }

    /**
     * @return array
     */
    public function getCategoryCountsDataProvider()
    {
        return [
            'without category' => [
                'expected' => [
                    '1' => 20,
                    '6' => 30,
                    '10' => 12,
                ]
            ],
            'with root category' => [
                'expected' => [
                    '2' => 20,
                ],
                'category' => $this->getCategory(1, '1')
            ],
            'with sub category' => [
                'expected' => [
                    '3' => 5,
                    '4' => 12,
                ],
                'category' => $this->getCategory(2, '1_2')
            ],
            'with unknown category' => [
                'expected' => [],
                'category' => $this->getCategory(100, '100')
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
