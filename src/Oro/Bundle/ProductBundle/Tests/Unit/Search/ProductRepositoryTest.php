<?php
namespace Oro\Bundle\ProductBundle\Tests\Unit\Search;

use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class ProductRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductRepository */
    protected $repository;

    /** @var QueryFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $queryFactory;

    /** @var AbstractSearchMappingProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $mappingProvider;

    protected function setUp()
    {
        $this->queryFactory = $this->getMock(QueryFactoryInterface::class);
        $this->mappingProvider = $this->getMockBuilder(AbstractSearchMappingProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntityAlias'])
            ->getMockForAbstractClass();
        $this->repository = new ProductRepository($this->queryFactory, $this->mappingProvider);
    }

    public function testGetProductSearchQuery()
    {
        $criteria = $this->getMock(Criteria::class);
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->setMethods(['setFrom', 'addSelect', 'getCriteria', 'andWhere'])->getMock();

        $query->method('setFrom')->withAnyParameters()->willReturn($query);
        $query->method('addSelect')->withAnyParameters()->willReturn($query);
        $query->method('getCriteria')->withAnyParameters()->willReturn($criteria);
        $query->method('andWhere')->withAnyParameters()->willReturn($query);

        $this->queryFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($query);

        $this->repository->getFilterSkuQuery(['test']);
        $this->assertEquals($query, $this->repository->createQuery());
    }
}