<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Query;

use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;

class WebsiteSearchQueryTest extends \PHPUnit\Framework\TestCase
{
    /** @var WebsiteSearchQuery */
    protected $websiteSearchQuery;

    /** @var EngineInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $engine;

    /** @var Query|\PHPUnit\Framework\MockObject\MockObject */
    protected $query;

    protected function setUp(): void
    {
        $this->engine = $this->getMockBuilder(EngineInterface::class)
            ->getMock();

        $this->query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteSearchQuery = new WebsiteSearchQuery(
            $this->engine,
            $this->query
        );
    }

    public function testAddSelect()
    {
        $name = 'name';
        $type = 'text';

        $this->query->expects($this->once())
            ->method('addSelect')
            ->with($name, $type);

        $this->websiteSearchQuery->addSelect($name, $type);
    }

    public function testFrom()
    {
        $alias = 'alias';

        $this->query->expects($this->once())
            ->method('from')
            ->with($alias);

        $this->websiteSearchQuery->setFrom($alias);
    }

    public function testWebsiteQueryExecution()
    {
        $result = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->engine->expects($this->once())
            ->method('search')
            ->with($this->query)
            ->willReturn($result);

        $this->websiteSearchQuery->execute();
    }

    public function testAggregationAccessors()
    {
        $this->query->expects($this->once())
            ->method('addAggregate')
            ->with('test_name', 'test_field', 'test_function');

        $this->websiteSearchQuery->addAggregate('test_name', 'test_field', 'test_function');

        $aggregations = ['test_name' => ['field' => 'test_field', 'function' => 'test_function']];

        $this->query->expects($this->once())
            ->method('getAggregations')
            ->willReturn($aggregations);

        $this->assertEquals($aggregations, $this->websiteSearchQuery->getAggregations());
    }

    public function testClone()
    {
        $query = new Query();
        $this->websiteSearchQuery = new WebsiteSearchQuery($this->engine, $query);

        $result1 = new Result($query);
        $result2 = new Result($query);

        $this->engine->expects($this->exactly(2))
            ->method('search')
            ->with($query)
            ->willReturnOnConsecutiveCalls($result1, $result2);

        $this->assertSame($result1, $this->websiteSearchQuery->getResult());
        $this->assertSame($query, $this->websiteSearchQuery->getQuery());

        $newQuery = clone $this->websiteSearchQuery;

        $this->assertSame($result2, $newQuery->getResult());
        $this->assertNotSame($query, $newQuery->getQuery());
        $this->assertEquals($query, $newQuery->getQuery());
    }
}
