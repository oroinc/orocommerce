<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Query;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchRepository;

class WebsiteSearchRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var WebsiteSearchRepository */
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
        $this->repository = new WebsiteSearchRepository($this->queryFactory, $this->mappingProvider);
    }

    public function testCreateQueryWithoutEntity()
    {
        $query = $this->getMock(SearchQueryInterface::class);

        $this->queryFactory->expects($this->once())
            ->method('create')
            ->with(['search_index' => 'website'])
            ->willReturn($query);
        $this->mappingProvider->expects($this->never())
            ->method($this->anything());

        $this->assertEquals($query, $this->repository->createQuery());
    }

    public function testCreateQueryWithEntity()
    {
        $entityClass = 'TestClass';
        $entityAlias = 'test_class';

        $query = $this->getMock(SearchQueryInterface::class);
        $query->expects($this->once())
            ->method('setFrom')
            ->with($entityAlias);

        $this->queryFactory->expects($this->once())
            ->method('create')
            ->with(['search_index' => 'website'])
            ->willReturn($query);

        $this->mappingProvider->expects($this->once())
            ->method('getEntityAlias')
            ->with($entityClass)
            ->willReturn($entityAlias);

        $this->repository->setEntityName($entityClass);
        $this->assertEquals($query, $this->repository->createQuery());
    }
}
