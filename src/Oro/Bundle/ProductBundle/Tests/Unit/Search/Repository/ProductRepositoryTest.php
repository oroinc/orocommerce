<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Search\Repository;

use Oro\Bundle\ProductBundle\Search\Repository\ProductRepository;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class ProductRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QueryFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $queryFactory;

    /**
     * @var AbstractSearchMappingProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mappingProvider;

    /**
     * @var ProductRepository
     */
    protected $testable;

    /**
     * @var SearchQueryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $searchQuery;

    protected function setUp()
    {
        $this->queryFactory = $this->getMockBuilder(QueryFactoryInterface::class)
            ->getMock();

        $this->mappingProvider = $this->getMockBuilder(AbstractSearchMappingProvider::class)
            ->getMockForAbstractClass();

        $this->testable = new ProductRepository(
            $this->queryFactory,
            $this->mappingProvider
        );

        $this->searchQuery = $this->getMockBuilder(
            SearchQueryInterface::class
        )->setMethods(
            [
                'setFrom',
                'addSelect',
                'getCriteria',
                'andWhere'
            ]
        )->getMockForAbstractClass();
    }

    public function testSkuFilterQueryCreation()
    {
        $skus = ['abc123', 'zyx456'];

        $this->searchQuery->expects($this->once())
            ->method('setFrom')
            ->with('product')
            ->willReturnSelf();

        $this->searchQuery->expects($this->once())
            ->method('addSelect')
            ->with('sku')
            ->willReturnSelf();

        $this->searchQuery->expects($this->once())
            ->method('getCriteria')
            ->willReturnSelf();

        $upperCaseSkus = ['ABC123', 'ZYX456'];

        $this->searchQuery->expects($this->once())
            ->method('andWhere')
            ->with(Criteria::expr()->in('sku_uppercase', $upperCaseSkus));

        $this->queryFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->searchQuery);

        $resultQuery = $this->testable->getFilterSkuQuery($skus);

        $this->assertEquals($resultQuery, $this->searchQuery);
    }
}
