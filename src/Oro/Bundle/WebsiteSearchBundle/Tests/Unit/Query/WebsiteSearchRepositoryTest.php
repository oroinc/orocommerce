<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Query;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchRepository;

class WebsiteSearchRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var WebsiteSearchRepository */
    private $repository;

    /** @var QueryFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $queryFactory;

    /** @var AbstractSearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $mappingProvider;

    protected function setUp(): void
    {
        $this->queryFactory = $this->createMock(QueryFactoryInterface::class);
        $this->mappingProvider = $this->createMock(AbstractSearchMappingProvider::class);

        $this->repository = new WebsiteSearchRepository($this->queryFactory, $this->mappingProvider);
    }

    public function testCreateQueryWithoutEntity()
    {
        $query = $this->createMock(SearchQueryInterface::class);

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

        $query = $this->createMock(SearchQueryInterface::class);
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
