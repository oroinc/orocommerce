<?php
/**
 * Created by PhpStorm.
 * User: mgz
 * Date: 06.10.16
 * Time: 12:37
 */

namespace Oro\Bundle\ProductBundle\Tests\Unit\Search;


use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
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
        $entityClass = 'Oro\Bundle\ProductBundle\Entity\Product';
        $entityAlias = 'product_WEBSITE_ID';

        $query = $this->getMock(SearchQueryInterface::class);

        $this->queryFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($query);

        $this->mappingProvider->expects($this->once())
            ->method('getEntityAlias')
            ->with($entityClass)
            ->willReturn('product_WEBSITE_ID');

        $this->repository->getProductSearchQuery('test', 0, 10);
        $this->assertEquals($query, $this->repository->createQuery());
    }
}